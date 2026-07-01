<?php

declare(strict_types=1);

namespace App\Actions\Listening\Evaluation;

use App\DTOs\Listening\Evaluation\ListeningAnswerEvaluationResultData;
use App\Models\Listening\ListeningAttemptAnswer;
use App\Models\Listening\ListeningQuestion;
use App\Services\Listening\Evaluation\ListeningQuestionEvaluatorRegistry;

class EvaluateListeningAttemptAnswerAction
{
    public function __construct(
        private readonly ListeningQuestionEvaluatorRegistry $evaluators,
    ) {}

    public function execute(
        ListeningAttemptAnswer $attemptAnswer,
        ListeningQuestion $question,
    ): ListeningAnswerEvaluationResultData {
        return $this->evaluators->evaluate($attemptAnswer, $question);
    }
}
