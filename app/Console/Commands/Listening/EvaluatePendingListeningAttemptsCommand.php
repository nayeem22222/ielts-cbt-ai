<?php

declare(strict_types=1);

namespace App\Console\Commands\Listening;

use App\Actions\Listening\Evaluation\EvaluateListeningAttemptAction;
use App\Repositories\Listening\Evaluation\ListeningAttemptEvaluationRepository;
use Illuminate\Console\Command;

class EvaluatePendingListeningAttemptsCommand extends Command
{
    protected $signature = 'listening:attempts:evaluate-pending {--limit=100 : Maximum attempts to evaluate}';

    protected $description = 'Evaluate submitted listening attempts that have not been scored yet';

    public function handle(
        ListeningAttemptEvaluationRepository $evaluations,
        EvaluateListeningAttemptAction $evaluate,
    ): int {
        $limit = max(1, (int) $this->option('limit'));
        $attempts = $evaluations->findAttemptsNeedingEvaluation($limit);
        $processed = 0;

        foreach ($attempts as $attempt) {
            try {
                $evaluate->execute($attempt, ['dispatch_only' => false]);
                $processed++;
            } catch (\Throwable $exception) {
                $this->error("Attempt {$attempt->id}: {$exception->getMessage()}");
            }
        }

        $this->info("Processed {$processed} listening attempt(s).");

        return self::SUCCESS;
    }
}
