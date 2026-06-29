<?php

declare(strict_types=1);

namespace App\Actions\Listening\Student;

use App\Enums\Listening\ListeningAnswerStatus;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningQuestion;
use App\Repositories\Listening\Student\ListeningAttemptAnswerRepository;
use App\Services\Listening\Student\ListeningQuestionPaletteService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ToggleListeningQuestionFlagAction
{
    public function __construct(
        private readonly ListeningAttemptAnswerRepository $answers,
        private readonly ListeningQuestionPaletteService $palette,
    ) {}

    public function execute(ListeningAttempt $attempt, ListeningQuestion $question, bool $flagged): array
    {
        if ((int) $question->listening_test_id !== (int) $attempt->listening_test_id) {
            throw ValidationException::withMessages([
                'question_id' => 'Question does not belong to this listening test.',
            ]);
        }

        DB::transaction(function () use ($attempt, $question, $flagged): void {
            $row = $this->answers->findForAttemptQuestion($attempt, $question->id);
            $meta = is_array($row?->meta) ? $row->meta : [];
            $meta['is_flagged'] = $flagged;

            if ($row === null) {
                $this->answers->createRowsForQuestions($attempt, collect([$question]));
                $row = $this->answers->findForAttemptQuestion($attempt, $question->id);
            }

            $isAnswered = $row?->student_answer !== null && $row->student_answer !== [];

            $row?->fill([
                'meta' => $meta,
                'answer_status' => $isAnswered
                    ? ListeningAnswerStatus::Answered
                    : ($flagged
                        ? ListeningAnswerStatus::Flagged
                        : ListeningAnswerStatus::Unanswered),
            ])->save();
        });

        return [
            'success' => true,
            'flagged' => $flagged,
            'palette' => $this->palette->build($attempt->refresh()),
        ];
    }
}
