<?php

declare(strict_types=1);

namespace App\Console\Commands\Listening;

use App\Actions\Listening\Audio\Pipeline\RetryStuckListeningAudioPipelineAction;
use Illuminate\Console\Command;

class RetryStuckListeningAudioCommand extends Command
{
    protected $signature = 'listening:audio:retry-stuck
                            {--dry-run : Show what would be retried without dispatching}
                            {--force : Force retry even if already processing}
                            {--limit=50 : Max number of stuck jobs to process}';

    protected $description = 'Find and retry stuck listening audio processing jobs.';

    public function handle(RetryStuckListeningAudioPipelineAction $action): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) ($this->option('limit') ?? 50);

        if ($dryRun) {
            $this->info('[DRY RUN] No jobs will be dispatched.');
        }

        $this->info('Searching for stuck listening audio processing jobs...');

        $stuckMinutes = (int) config('listening.audio_pipeline.retry_stuck_after_minutes', 30);
        $this->line("  Threshold: stuck for more than {$stuckMinutes} minutes.");
        $this->line('  Limit: '.$limit);

        $result = $action->execute(dryRun: $dryRun, limit: $limit);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Stuck audios found', $result['found']],
                ['Jobs dispatched', $result['dispatched']],
                ['Skipped (dry-run or max-retries)', $result['skipped']],
            ]
        );

        if ($result['found'] === 0) {
            $this->info('No stuck audio processing jobs found.');
        } elseif ($dryRun) {
            $this->comment('Dry-run complete. Run without --dry-run to dispatch retry jobs.');
        } else {
            $this->info("Dispatched {$result['dispatched']} retry job(s).");
        }

        return self::SUCCESS;
    }
}
