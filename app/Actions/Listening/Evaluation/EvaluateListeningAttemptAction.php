<?php

declare(strict_types=1);

namespace App\Actions\Listening\Evaluation;

use App\DTOs\Listening\Evaluation\ListeningEvaluationResultData;
use App\Models\Listening\ListeningAttempt;
use App\Services\Listening\Evaluation\ListeningAnswerEngineService;
use App\Services\Listening\Evaluation\ListeningEvaluationDispatchService;

class EvaluateListeningAttemptAction
{
    public function __construct(
        private readonly ListeningAnswerEngineService $engine,
        private readonly ListeningEvaluationDispatchService $dispatch,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function execute(ListeningAttempt $attempt, array $options = []): ListeningEvaluationResultData|string|null
    {
        if (! config('listening.answer_engine.evaluate_on_submit', true)) {
            return null;
        }

        if (($options['dispatch_only'] ?? false) === true) {
            $this->dispatch->dispatch($attempt, $options);

            return null;
        }

        if ($this->dispatch->shouldQueue()) {
            $this->dispatch->dispatch($attempt, $options);

            return null;
        }

        return $this->engine->evaluateAttempt($attempt, $options);
    }
}
