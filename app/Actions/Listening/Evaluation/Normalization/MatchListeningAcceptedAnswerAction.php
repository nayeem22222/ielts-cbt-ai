<?php

declare(strict_types=1);

namespace App\Actions\Listening\Evaluation\Normalization;

use App\DTOs\Listening\Evaluation\Normalization\AcceptedAnswerMatchData;
use App\Models\Listening\ListeningQuestion;
use App\Services\Listening\Evaluation\Normalization\ListeningAcceptedAnswerMatcher;

class MatchListeningAcceptedAnswerAction
{
    public function __construct(
        private readonly ListeningAcceptedAnswerMatcher $matcher,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $correctAnswers
     * @param  list<array<string, mixed>>  $acceptedAnswers
     */
    public function execute(
        mixed $studentAnswer,
        array $correctAnswers,
        array $acceptedAnswers,
        ListeningQuestion $question,
    ): AcceptedAnswerMatchData {
        return $this->matcher->match($studentAnswer, $correctAnswers, $acceptedAnswers, $question);
    }
}
