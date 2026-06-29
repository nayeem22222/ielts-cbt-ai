<?php

declare(strict_types=1);

namespace App\Services\Listening\Student;

use App\Actions\Listening\NormalizeListeningAnswerDataAction;
use App\Enums\Listening\ListeningAnswerStatus;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningAttemptAnswer;
use App\Models\Listening\ListeningQuestion;
use App\Repositories\Listening\Student\ListeningAttemptAnswerRepository;
use App\Repositories\Listening\Student\ListeningAttemptRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ListeningAnswerSaveService
{
    public function __construct(
        private readonly ListeningAttemptRepository $attempts,
        private readonly ListeningAttemptAnswerRepository $answers,
        private readonly NormalizeListeningAnswerDataAction $normalizeAnswer,
    ) {}

    /**
     * @param  array<string, mixed>|list<array<string, mixed>>|string|null  $studentAnswer
     */
    public function save(
        ListeningAttempt $attempt,
        ListeningQuestion $question,
        array|string|null $studentAnswer,
    ): ListeningAttemptAnswer {
        if ((int) $question->listening_test_id !== (int) $attempt->listening_test_id) {
            throw ValidationException::withMessages([
                'question_id' => 'Question does not belong to this listening test.',
            ]);
        }

        if (! $question->is_active) {
            throw ValidationException::withMessages([
                'question_id' => 'Question is not active.',
            ]);
        }

        $defaultType = $question->answer_format?->value ?? 'text';
        $normalizedStudent = $this->normalizeAnswer->execute($studentAnswer, $defaultType);
        $normalizedPlaceholder = $this->normalizeAnswer->execute($studentAnswer, $defaultType);
        $isAnswered = $normalizedStudent !== [];

        return DB::transaction(function () use (
            $attempt,
            $question,
            $normalizedStudent,
            $normalizedPlaceholder,
            $isAnswered,
        ): ListeningAttemptAnswer {
            $row = $this->answers->findForAttemptQuestion($attempt, $question->id);

            if ($row === null) {
                $row = ListeningAttemptAnswer::query()->create([
                    'listening_attempt_id' => $attempt->id,
                    'listening_test_id' => $attempt->listening_test_id,
                    'listening_question_id' => $question->id,
                    'question_number' => $question->question_number,
                    'student_answer' => $isAnswered ? $normalizedStudent : null,
                    'normalized_answer' => $isAnswered ? $normalizedPlaceholder : null,
                    'answer_status' => $isAnswered ? ListeningAnswerStatus::Answered : ListeningAnswerStatus::Unanswered,
                    'answered_at' => $isAnswered ? now() : null,
                ]);
            } else {
                $meta = is_array($row->meta) ? $row->meta : [];
                $isFlagged = ($meta['is_flagged'] ?? false) === true;

                $row->fill([
                    'student_answer' => $isAnswered ? $normalizedStudent : null,
                    'normalized_answer' => $isAnswered ? $normalizedPlaceholder : null,
                    'answer_status' => $isAnswered
                        ? ListeningAnswerStatus::Answered
                        : ($isFlagged ? ListeningAnswerStatus::Flagged : ListeningAnswerStatus::Unanswered),
                    'answered_at' => $isAnswered ? now() : null,
                ])->save();
            }

            $this->attempts->update($attempt, [
                'total_answered' => $this->answers->countAnswered($attempt->refresh()),
            ]);

            return $row->refresh();
        });
    }

    /**
     * @param  list<array{question_id: int, student_answer: mixed}>  $items
     * @return list<ListeningAttemptAnswer>
     */
    public function bulkSave(ListeningAttempt $attempt, array $items): array
    {
        $saved = [];

        foreach ($items as $item) {
            $question = ListeningQuestion::query()->find((int) ($item['question_id'] ?? 0));

            if ($question === null) {
                continue;
            }

            $saved[] = $this->save($attempt, $question, $item['student_answer'] ?? null);
        }

        return $saved;
    }
}
