<?php

declare(strict_types=1);

namespace App\Jobs\Listening\Audio;

use App\Enums\Listening\ListeningAudioProcessingStatus;
use App\Enums\Listening\ListeningAudioValidationStatus;
use App\Models\Listening\ListeningAudio;
use App\Services\Listening\Audio\Pipeline\ListeningAudioPipelineLockService;
use App\Services\Listening\Audio\Pipeline\ListeningAudioPipelineLogger;
use App\Services\Listening\Audio\Pipeline\ListeningAudioPipelineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessListeningAudioPipelineJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout;

    public int $tries;

    /** @var list<int> */
    public array $backoff;

    public function __construct(
        public readonly int $audioId,
        public readonly bool $force = false,
    ) {
        $this->timeout = (int) config('listening.audio_pipeline.job_timeout_seconds', 900);
        $this->tries = (int) config('listening.audio_pipeline.job_tries', 3);
        $this->backoff = (array) config('listening.audio_pipeline.backoff_seconds', [60, 300, 900]);
    }

    public function handle(
        ListeningAudioPipelineService $pipeline,
        ListeningAudioPipelineLockService $lockService,
        ListeningAudioPipelineLogger $logger,
    ): void {
        $audio = ListeningAudio::query()->find($this->audioId);

        if ($audio === null) {
            // Audio was deleted — stop silently
            return;
        }

        $rawJobId = $this->job?->getJobId();
        $jobId = $rawJobId !== null ? (string) $rawJobId : null;

        try {
            $pipeline->process($audio, $this->force, $jobId);
        } catch (\DomainException $e) {
            // Permanent failures — mark failed and do not re-queue
            $this->permanentlyFail($audio, $lockService, $logger, $e);
            $this->fail($e);
        } catch (\Throwable $e) {
            // Retryable failure — let Laravel retry based on $tries / $backoff
            $this->recordRetryableFailure($audio, $e);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $audio = ListeningAudio::query()->find($this->audioId);

        if ($audio === null) {
            return;
        }

        DB::table('listening_audios')
            ->where('id', $audio->id)
            ->update([
                'processing_status' => ListeningAudioProcessingStatus::Failed->value,
                'validation_status' => ListeningAudioValidationStatus::Invalid->value,
                'validation_errors' => json_encode([[
                    'code' => 'audio_pipeline_failed',
                    'message' => substr($exception->getMessage(), 0, 1000),
                ]]),
                'processing_error' => substr($exception->getMessage(), 0, 65535),
                'processing_finished_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function permanentlyFail(
        ListeningAudio $audio,
        ListeningAudioPipelineLockService $lockService,
        ListeningAudioPipelineLogger $logger,
        \DomainException $e,
    ): void {
        DB::table('listening_audios')
            ->where('id', $audio->id)
            ->update([
                'processing_status' => ListeningAudioProcessingStatus::Failed->value,
                'validation_status' => ListeningAudioValidationStatus::Invalid->value,
                'validation_errors' => json_encode([[
                    'code' => 'audio_pipeline_failed',
                    'message' => substr($e->getMessage(), 0, 1000),
                ]]),
                'processing_error' => substr($e->getMessage(), 0, 65535),
                'processing_finished_at' => now(),
                'updated_at' => now(),
            ]);

        // Force release lock if still held
        if ($lockService->isLocked($audio)) {
            $lockService->forceRelease($audio);
        }

        $logger->updateMetaHistory(
            $audio,
            'failed',
            'failed',
            'Permanent failure: '.$e->getMessage(),
        );
    }

    private function recordRetryableFailure(ListeningAudio $audio, \Throwable $e): void
    {
        DB::table('listening_audios')
            ->where('id', $audio->id)
            ->update([
                'processing_error' => substr('Retryable: '.$e->getMessage(), 0, 65535),
                'updated_at' => now(),
            ]);
    }
}
