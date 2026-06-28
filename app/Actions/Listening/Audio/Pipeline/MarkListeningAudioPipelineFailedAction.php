<?php

declare(strict_types=1);

namespace App\Actions\Listening\Audio\Pipeline;

use App\Enums\Listening\ListeningAudioProcessingStatus;
use App\Enums\Listening\ListeningAudioValidationStatus;
use App\Models\Listening\ListeningAudio;
use App\Services\Listening\Audio\Pipeline\ListeningAudioPipelineLockService;
use App\Services\Listening\Audio\Pipeline\ListeningAudioPipelineLogger;
use Illuminate\Support\Facades\DB;

class MarkListeningAudioPipelineFailedAction
{
    public function __construct(
        private readonly ListeningAudioPipelineLockService $lockService,
        private readonly ListeningAudioPipelineLogger $logger,
    ) {}

    public function execute(
        ListeningAudio $audio,
        \Throwable|string $error,
        string $stage = 'failed',
        bool $invalidateValidation = true,
        ?string $lockToken = null,
    ): void {
        $message = $error instanceof \Throwable ? $error->getMessage() : $error;

        DB::table('listening_audios')
            ->where('id', $audio->id)
            ->update([
                'processing_status' => ListeningAudioProcessingStatus::Failed->value,
                'validation_status' => $invalidateValidation
                    ? ListeningAudioValidationStatus::Invalid->value
                    : DB::raw('validation_status'),
                'processing_error' => substr($message, 0, 65535),
                'processing_finished_at' => now(),
                'retry_count' => DB::raw('retry_count + 1'),
                'updated_at' => now(),
            ]);

        $this->logger->updateMetaHistory($audio, $stage, 'failed', $message);

        if ($lockToken !== null) {
            $this->lockService->release($audio, $lockToken);
        }
    }
}
