<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation;

use App\Jobs\Listening\Evaluation\EvaluateListeningAttemptJob;
use App\Models\Listening\ListeningAttempt;

class ListeningEvaluationDispatchService
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function dispatch(ListeningAttempt $attempt, array $options = []): void
    {
        if (! config('listening.answer_engine.evaluate_on_submit', true)) {
            return;
        }

        if ($this->shouldQueue()) {
            EvaluateListeningAttemptJob::dispatch($attempt->id, $options)
                ->onQueue((string) config('listening.answer_engine.queue', 'default'));

            return;
        }

        app(ListeningAnswerEngineService::class)->evaluateAttempt($attempt, $options);
    }

    public function shouldQueue(): bool
    {
        return config('listening.answer_engine.mode', 'queue') === 'queue';
    }
}
