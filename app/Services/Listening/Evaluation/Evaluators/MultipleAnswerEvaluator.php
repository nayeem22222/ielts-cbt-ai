<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation\Evaluators;

use App\DTOs\Listening\Evaluation\ListeningAnswerEvaluationResultData;
use App\Enums\Listening\ListeningMatchStatus;
use App\Models\Listening\ListeningAttemptAnswer;
use App\Models\Listening\ListeningQuestion;
use App\Support\Listening\Evaluation\ListeningMatchReason;

class MultipleAnswerEvaluator extends BaseListeningQuestionEvaluator
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

        $orderSensitive = (bool) ($question->order_sensitive ?? config('listening.answer_engine.multiple_answer.order_sensitive', false));
        $match = $this->matcher->matchSet(
            studentAnswer: $this->forceType($raw, 'letter'),
            correctAnswers: $this->forceType($this->snapshots->correctAnswerSnapshot($question), 'letter'),
            question: $question,
            orderSensitive: $orderSensitive,
        );
        $normalized = $this->normalizedFromMatch($match, 'letter');
        $studentSet = $normalized->values;

        if ($studentSet === []) {
            return $this->unanswered($attemptAnswer, $question);
        }

        $partialEnabled = (bool) config('listening.answer_engine.multiple_answer.partial_marking', false);
        $marks = $this->marksAvailable($question);

        if ($match->matched) {
            return $this->buildResult(
                attemptAnswer: $attemptAnswer,
                question: $question,
                normalized: $normalized,
                isCorrect: true,
                marksAwarded: $marks,
                matchStatus: ListeningMatchStatus::Correct,
                matchReason: ListeningMatchReason::EXACT_MATCH,
                matchedAnswer: array_map(fn (string $v): array => ['value' => $v, 'type' => 'letter'], $match->normalizedCorrectAnswers),
            );
        }

        $correctSet = $match->normalizedCorrectAnswers;
        $correctCount = count(array_intersect($studentSet, $correctSet));

        if ($partialEnabled && $correctCount > 0 && count($correctSet) > 0) {
            $awarded = round($marks * ($correctCount / count($correctSet)), 2);

            return $this->buildResult(
                attemptAnswer: $attemptAnswer,
                question: $question,
                normalized: $normalized,
                isCorrect: false,
                marksAwarded: $awarded,
                matchStatus: ListeningMatchStatus::Partial,
                matchReason: ListeningMatchReason::PARTIAL_MATCH,
            );
        }

        return $this->buildResult(
            attemptAnswer: $attemptAnswer,
            question: $question,
            normalized: $normalized,
            isCorrect: false,
            marksAwarded: 0.0,
            matchStatus: ListeningMatchStatus::Incorrect,
            matchReason: ListeningMatchReason::INCORRECT_ANSWER,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function forceType(array $items, string $type): array
    {
        return array_map(function (array $item) use ($type): array {
            $item['type'] = $type;

            return $item;
        }, $items);
    }
}
