<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Enums\Exam\TestAttemptStatus;
use App\Models\ReadingAttempt;
use App\Models\ReadingQuestion;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ReadingSubmitService
{
    public function __construct(
        private readonly ReadingTimerService $timer,
        private readonly ReadingAnswerService $answers,
        private readonly ReadingEvaluationService $evaluation,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function submit(ReadingAttempt $attempt, bool $auto = false): array
    {
        $this->timer->assertOwnedByUser($attempt);

        if ($attempt->status !== TestAttemptStatus::InProgress) {
            throw new ConflictHttpException('This attempt has already been submitted.');
        }

        $remaining = $this->timer->remainingSeconds($attempt);

        return DB::transaction(function () use ($attempt, $auto, $remaining): array {
            $attempt->update([
                'status' => TestAttemptStatus::Submitted,
                'submitted_at' => now(),
                'remaining_seconds' => max(0, $remaining),
                'metadata' => array_merge($attempt->metadata ?? [], [
                    'submitted_via' => $auto ? 'auto' : 'manual',
                ]),
            ]);

            $evaluation = $this->evaluation->evaluateAttempt($attempt->fresh());

            return [
                'success' => true,
                'status' => TestAttemptStatus::Completed->value,
                'submitted_at' => $attempt->fresh()->submitted_at?->toIso8601String(),
                'redirect_url' => route('reading-attempts.result', $attempt),
                'auto' => $auto,
                'evaluation' => $evaluation,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function autoSubmit(ReadingAttempt $attempt): array
    {
        if ($attempt->status !== TestAttemptStatus::InProgress) {
            return [
                'success' => true,
                'status' => $attempt->status?->value,
                'redirect_url' => route('reading-attempts.result', $attempt),
                'auto' => true,
            ];
        }

        return $this->submit($attempt, true);
    }

    public function assertWritable(ReadingAttempt $attempt): void
    {
        try {
            $this->answers->assertWritableAttempt($attempt);
        } catch (AuthorizationException|ConflictHttpException $exception) {
            throw $exception;
        }
    }

    public function markVisited(ReadingAttempt $attempt, ReadingQuestion $question): array
    {
        $this->answers->assertWritableAttempt($attempt);
        $this->answers->assertQuestionBelongsToAttemptTest($attempt, $question);

        $metadata = $attempt->metadata ?? [];
        $visited = $metadata['visited_questions'] ?? [];

        if (! in_array($question->question_number, $visited, true)) {
            $visited[] = $question->question_number;
            sort($visited, SORT_NUMERIC);
        }

        $metadata['visited_questions'] = array_values($visited);
        $attempt->update(['metadata' => $metadata]);

        return array_values($visited);
    }

    /**
     * @return list<int>
     */
    public function visitedQuestions(ReadingAttempt $attempt): array
    {
        $visited = $attempt->metadata['visited_questions'] ?? [];

        return is_array($visited) ? array_values(array_map('intval', $visited)) : [];
    }
}
