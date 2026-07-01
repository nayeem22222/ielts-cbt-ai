<?php

declare(strict_types=1);

namespace App\Actions\Listening\Evaluation;

use App\DTOs\Listening\Evaluation\ListeningAnswerEvaluationResultData;

class CreateListeningEvaluationSnapshotAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(ListeningAnswerEvaluationResultData $result): array
    {
        return [
            'listening_attempt_answer_id' => $result->attemptAnswerId,
            'listening_question_id' => $result->questionId,
            'question_number' => $result->questionNumber,
            'question_type' => $result->questionType,
            'student_answer_snapshot' => $result->studentAnswerSnapshot,
            'normalized_student_answer' => $result->normalizedStudentAnswer,
            'correct_answer_snapshot' => $result->correctAnswerSnapshot,
            'accepted_answers_snapshot' => $result->acceptedAnswersSnapshot,
            'matched_answer' => $result->matchedAnswer,
            'is_correct' => $result->isCorrect,
            'marks_available' => $result->marksAvailable,
            'marks_awarded' => $result->marksAwarded,
            'match_status' => $result->matchStatus->value,
            'match_reason' => $result->matchReason,
            'normalization_steps' => $result->normalizationSteps,
            'evaluator_meta' => $result->evaluatorMeta,
        ];
    }
}
