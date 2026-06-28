<?php

declare(strict_types=1);

namespace App\Actions\Listening\Audio\Pipeline;

use App\Enums\Listening\ListeningAudioProcessingStatus;
use App\Enums\Listening\ListeningAudioValidationStatus;
use App\Models\Listening\ListeningAudio;
use App\Services\Listening\Audio\Pipeline\ListeningAudioPipelineDispatcher;
use Illuminate\Support\Facades\DB;

class StartListeningAudioPipelineAction
{
    /**
     * Dispatch the audio pipeline job.
     *
     * @param  bool  $force  Bypass duplicate-dispatch protection.
     */
    public function execute(ListeningAudio $audio, bool $force = false): bool
    {
        if (! $force && $this->isAlreadyProcessing($audio)) {
            return false;
        }

        if (! $force && ListeningAudioPipelineDispatcher::hasQueuedJobForAudio($audio->id)) {
            return true;
        }

        // Mark as pending. Normal retry from a failed state increments retry_count;
        // first-time starts and force retries preserve the current count.
        $retryCountUpdate = (! $force && $audio->processing_status === ListeningAudioProcessingStatus::Failed)
            ? DB::raw('retry_count + 1')
            : $audio->retry_count;

        DB::table('listening_audios')
            ->where('id', $audio->id)
            ->update([
                'processing_status' => ListeningAudioProcessingStatus::Pending->value,
                'validation_status' => ListeningAudioValidationStatus::Pending->value,
                'validation_errors' => null,
                'processing_error' => null,
                'processing_started_at' => null,
                'processing_finished_at' => null,
                'retry_count' => $retryCountUpdate,
                'updated_at' => now(),
            ]);

        ListeningAudioPipelineDispatcher::dispatch($audio->id, $force);

        return true;
    }

    private function isAlreadyProcessing(ListeningAudio $audio): bool
    {
        $fresh = ListeningAudio::query()->find($audio->id);

        if ($fresh === null) {
            return false;
        }

        // Only block if actively running (a queue worker has the job). 
        // Pending = queued but not started; allow re-dispatch to recover stuck queues.
        return $fresh->processing_status === ListeningAudioProcessingStatus::Processing;
    }
}
