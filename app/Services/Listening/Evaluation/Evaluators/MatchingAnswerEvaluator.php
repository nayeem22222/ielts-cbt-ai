<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation\Evaluators;

use App\DTOs\Listening\Evaluation\ListeningAnswerEvaluationResultData;
use App\Models\Listening\ListeningAttemptAnswer;
use App\Models\Listening\ListeningQuestion;

class MatchingAnswerEvaluator extends LetterAnswerEvaluator
{
    public function evaluate(
        ListeningAttemptAnswer $attemptAnswer,
        ListeningQuestion $question,
    ): ListeningAnswerEvaluationResultData {
        $raw = $this->studentAnswer($attemptAnswer);

        if ($raw === null || $raw === []) {
            return $this->unanswered($attemptAnswer, $question);
        }

        $first = $raw[0] ?? null;

        if (is_array($first) && isset($first['item_key'])) {
            $match = $this->matcher->match(
                studentAnswer: [['value' => (string) ($first['value'] ?? ''), 'type' => 'matching', 'item_key' => $first['item_key']]],
                correctAnswers: $this->forceType($this->snapshots->correctAnswerSnapshot($question), 'matching'),
                acceptedAnswers: $this->forceType($this->snapshots->acceptedAnswersSnapshot($question), 'matching'),
                question: $question,
            );

            return $this->matchResult($attemptAnswer, $question, $match, 'matching');
        }

        return parent::evaluate($attemptAnswer, $question);
    }
}
