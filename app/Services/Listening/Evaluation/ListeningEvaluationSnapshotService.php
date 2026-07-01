<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation;

use App\Models\Listening\ListeningAttemptAnswer;
use App\Models\Listening\ListeningQuestion;

class ListeningEvaluationSnapshotService
{
    /**
     * @return array<string, mixed>
     */
    public function studentAnswerSnapshot(ListeningAttemptAnswer $attemptAnswer): ?array
    {
        if (! config('listening.answer_engine.preserve_snapshots', true)) {
            return $attemptAnswer->student_answer;
        }

        return $attemptAnswer->student_answer;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function correctAnswerSnapshot(ListeningQuestion $question): array
    {
        return is_array($question->correct_answer) ? $question->correct_answer : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function acceptedAnswersSnapshot(ListeningQuestion $question): array
    {
        return is_array($question->accepted_answers) ? $question->accepted_answers : [];
    }
}
