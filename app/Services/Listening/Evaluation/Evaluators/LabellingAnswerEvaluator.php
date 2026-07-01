<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation\Evaluators;

use App\DTOs\Listening\Evaluation\ListeningAnswerEvaluationResultData;
use App\Enums\Listening\ListeningMatchStatus;
use App\Models\Listening\ListeningAttemptAnswer;
use App\Models\Listening\ListeningQuestion;
use App\Support\Listening\Evaluation\ListeningMatchReason;

class LabellingAnswerEvaluator extends BaseListeningQuestionEvaluator
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
                $this->normalizer->normalize($raw, $question, 'map_label'),
            );
        }

        $first = $raw[0] ?? [];

        if (! is_array($first)) {
            return $this->buildResult(
                attemptAnswer: $attemptAnswer,
                question: $question,
                normalized: $this->normalizer->normalize($raw, $question, 'map_label'),
                isCorrect: false,
                marksAwarded: 0.0,
                matchStatus: ListeningMatchStatus::Incorrect,
                matchReason: ListeningMatchReason::INVALID_ANSWER_FORMAT,
            );
        }

        $type = str_contains($question->question_type->value, 'diagram') ? 'diagram_label' : 'map_label';
        $student = [[
            'label' => (string) ($first['label'] ?? $first['value'] ?? ''),
            'value' => (string) ($first['label'] ?? $first['value'] ?? ''),
            'type' => $type,
        ]];

        $match = $this->matcher->match(
            studentAnswer: $student,
            correctAnswers: $this->forceLabelType($this->snapshots->correctAnswerSnapshot($question), $type),
            acceptedAnswers: $this->forceLabelType($this->snapshots->acceptedAnswersSnapshot($question), $type),
            question: $question,
        );

        return $this->matchResult($attemptAnswer, $question, $match, $type);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function forceLabelType(array $items, string $type): array
    {
        return array_map(function (array $item) use ($type): array {
            $item['type'] = $type;
            $item['value'] = (string) ($item['label'] ?? $item['value'] ?? '');

            return $item;
        }, $items);
    }
}
