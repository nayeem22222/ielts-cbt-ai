<?php

declare(strict_types=1);

namespace App\Jobs\Listening\Evaluation;

use App\Models\Listening\ListeningAttempt;
use App\Services\Listening\Evaluation\ListeningAnswerEngineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EvaluateListeningAttemptJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public readonly int $attemptId,
        public readonly array $options = [],
    ) {}

    public function handle(ListeningAnswerEngineService $engine): void
    {
        $attempt = ListeningAttempt::query()->find($this->attemptId);

        if ($attempt === null) {
            Log::warning('listening.evaluation.job.missing_attempt', ['attempt_id' => $this->attemptId]);

            return;
        }

        if (! $engine->canEvaluate($attempt)) {
            Log::info('listening.evaluation.job.skipped', [
                'attempt_id' => $attempt->id,
                'status' => $attempt->status?->value,
            ]);

            return;
        }

        $engine->evaluateAttempt($attempt, $this->options);
    }
}
