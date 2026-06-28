<?php

declare(strict_types=1);

namespace App\Actions\Listening\Audio\Pipeline;

use App\Enums\Listening\ListeningAudioProcessingStatus;
use App\Models\Listening\ListeningAudio;
use App\Services\Listening\Audio\Pipeline\ListeningAudioPipelineLockService;
use Illuminate\Database\Eloquent\Collection;

class RetryStuckListeningAudioPipelineAction
{
    public function __construct(
        private readonly ListeningAudioPipelineLockService $lockService,
        private readonly StartListeningAudioPipelineAction $startPipeline,
    ) {}

    /**
     * Find stuck processing audio and re-dispatch.
     *
     * @return array{found: int, dispatched: int, skipped: int}
     */
    public function execute(bool $dryRun = false, int $limit = 50): array
    {
        $stuckMinutes = (int) config('listening.audio_pipeline.retry_stuck_after_minutes', 30);
        $maxRetries = (int) config('listening.audio_pipeline.max_retry_count', 3);

        /** @var Collection<int, ListeningAudio> $stuckAudios */
        $stuckAudios = ListeningAudio::query()
            ->where('processing_status', ListeningAudioProcessingStatus::Processing->value)
            ->where('processing_started_at', '<=', now()->subMinutes($stuckMinutes))
            ->where('retry_count', '<', $maxRetries)
            ->whereNull('deleted_at')
            ->orderBy('processing_started_at')
            ->limit($limit)
            ->get();

        $found = $stuckAudios->count();
        $dispatched = 0;
        $skipped = 0;

        foreach ($stuckAudios as $audio) {
            if ($dryRun) {
                $skipped++;

                continue;
            }

            // Force release stale lock
            if ($this->lockService->isLocked($audio)) {
                $this->lockService->forceRelease($audio);
            }

            $this->startPipeline->execute($audio, force: false) ? $dispatched++ : $skipped++;
        }

        return compact('found', 'dispatched', 'skipped');
    }
}
