<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation\Evaluators;

use App\DTOs\Listening\Evaluation\ListeningAnswerEvaluationResultData;
use App\Enums\Listening\ListeningMatchStatus;
use App\Models\Listening\ListeningAttemptAnswer;
use App\Models\Listening\ListeningQuestion;
use App\Support\Listening\Evaluation\ListeningMatchReason;

class TextAnswerEvaluator extends BaseListeningQuestionEvaluator
{
    public function evaluate(
        ListeningAttemptAnswer $attemptAnswer,
        ListeningQuestion $question,
    ): ListeningAnswerEvaluationResultData {
        $raw = $this->studentAnswer($attemptAnswer);

        if ($raw === null || $raw === []) {
            return $this->unanswered($attemptAnswer, $question);
        }

        if (! $this->hasCorrectAnswerKey($question)) {
            return $this->manualReview(
                $attemptAnswer,
                $question,
                ListeningMatchReason::MANUAL_REVIEW_REQUIRED,
                $this->normalizer->normalize($raw, $question, 'text'),
            );
        }

        if ((bool) config('listening.normalization.word_limit.enforce', true) && $this->wordLimit->exceedsLimit($raw, $question)) {
            return $this->buildResult(
                attemptAnswer: $attemptAnswer,
                question: $question,
                normalized: $this->normalizer->normalize($raw, $question, 'text'),
                isCorrect: false,
                marksAwarded: 0.0,
                matchStatus: ListeningMatchStatus::Incorrect,
                matchReason: ListeningMatchReason::WORD_LIMIT_EXCEEDED,
            );
        }

        $match = $this->matcher->match(
            studentAnswer: $raw,
            correctAnswers: $this->snapshots->correctAnswerSnapshot($question),
            acceptedAnswers: $this->snapshots->acceptedAnswersSnapshot($question),
            question: $question,
        );

        if ($match->normalizedStudentAnswer->isEmpty()) {
            return $this->unanswered($attemptAnswer, $question);
        }

        return $this->matchResult($attemptAnswer, $question, $match, 'text');
    }
}
