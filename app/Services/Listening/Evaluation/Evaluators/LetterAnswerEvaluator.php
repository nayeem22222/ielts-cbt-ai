<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation\Evaluators;

use App\DTOs\Listening\Evaluation\ListeningAnswerEvaluationResultData;
use App\Enums\Listening\ListeningMatchStatus;
use App\Models\Listening\ListeningAttemptAnswer;
use App\Models\Listening\ListeningQuestion;
use App\Support\Listening\Evaluation\ListeningMatchReason;

class LetterAnswerEvaluator extends BaseListeningQuestionEvaluator
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
                $this->normalizer->normalize($raw, $question, 'letter'),
            );
        }

        $match = $this->matcher->match(
            studentAnswer: $this->forceType($raw, 'letter'),
            correctAnswers: $this->forceType($this->snapshots->correctAnswerSnapshot($question), 'letter'),
            acceptedAnswers: $this->forceType($this->snapshots->acceptedAnswersSnapshot($question), 'letter'),
            question: $question,
        );
        $normalized = $this->normalizedFromMatch($match, 'letter');

        if ($normalized->isEmpty()) {
            return $this->buildResult(
                attemptAnswer: $attemptAnswer,
                question: $question,
                normalized: $normalized,
                isCorrect: false,
                marksAwarded: 0.0,
                matchStatus: ListeningMatchStatus::Incorrect,
                matchReason: ListeningMatchReason::INVALID_ANSWER_FORMAT,
            );
        }

        return $this->matchResult($attemptAnswer, $question, $match, 'letter');
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    protected function forceType(array $items, string $type): array
    {
        return array_map(function (array $item) use ($type): array {
            $item['type'] = $type;

            return $item;
        }, $items);
    }
}
