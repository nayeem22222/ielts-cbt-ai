<?php

declare(strict_types=1);

namespace App\Services\Listening\Audio\Pipeline;

use App\DTOs\Listening\Audio\Pipeline\AudioPipelineContextData;
use App\Enums\Listening\ListeningAudioProcessingStatus;
use App\Enums\Listening\ListeningAudioValidationStatus;
use App\Models\Listening\ListeningAudio;
use App\Models\Listening\ListeningAudioProcessingLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ListeningAudioPipelineService
{
    public function __construct(
        private readonly ListeningAudioPipelineLockService $lockService,
        private readonly ListeningAudioPipelineLogger $logger,
        private readonly FfmpegBinaryService $ffmpegBinary,
        private readonly FfprobeMetadataReader $metadataReader,
        private readonly AudioConversionPipelineStep $conversionStep,
        private readonly AudioNormalizationPipelineStep $normalizationStep,
        private readonly AudioSilenceDetectionStep $silenceStep,
        private readonly AudioWaveformPipelineStep $waveformStep,
        private readonly AudioFileVerificationStep $verificationStep,
    ) {}

    /**
     * Run the full pipeline for an audio file.
     *
     * @param  bool  $force  Re-process even if already completed.
     *
     * @throws RuntimeException on permanent or retryable failure
     */
    public function process(ListeningAudio $audio, bool $force = false, ?string $jobId = null): void
    {
        $pipelineVersion = (string) config('listening.audio_pipeline.version', '1.0.0');

        $context = new AudioPipelineContextData(
            audioId: $audio->id,
            force: $force,
            pipelineVersion: $pipelineVersion,
        );

        $context->jobId = $jobId;

        // Step 1: Check if already completed and force=false
        if (! $force && $this->isAlreadyCompleted($audio)) {
            $this->logger->skipped($audio, 'completed', 'Audio already processed. Use force=true to reprocess.', [], $jobId);

            return;
        }

        // Step 2: Acquire lock
        $log = $this->logger->startStage($audio, 'lock_acquired', [], $jobId);

        try {
            $token = $this->lockService->acquire($audio);
            $context->lockToken = $token;
            $this->logger->completeStage($log, 'Processing lock acquired.');
        } catch (\Throwable $e) {
            $this->logger->failStage($log, $e);
            throw $e;
        }

        // Mark processing
        $this->markProcessing($audio, $pipelineVersion);

        // Log queued → lock_acquired
        $this->logger->updateMetaHistory($audio, 'queued', 'completed', 'Job dispatched and started.');

        try {
            // Step 3: Verify source file
            $this->runStep($audio, $context, 'verified', function () use ($audio, &$context): void {
                $disk = Storage::disk((string) config('listening.audio.disk', 'public'));

                if (! $disk->exists($audio->path)) {
                    throw new \DomainException("Original audio file is missing: {$audio->path}");
                }
            }, $jobId);

            // Step 4: Check FFmpeg
            $this->runStep($audio, $context, 'ffmpeg_checked', function (): void {
                $this->ffmpegBinary->assertFfmpegAvailable();
            }, $jobId);

            // Step 5: Check FFprobe
            $this->runStep($audio, $context, 'ffprobe_checked', function (): void {
                $this->ffmpegBinary->assertFfprobeAvailable();
            }, $jobId);

            // Step 6: Extract metadata
            $metaLog = $this->logger->startStage($audio, 'metadata_extracted', [], $jobId);

            try {
                $disk = Storage::disk((string) config('listening.audio.disk', 'public'));
                $absolutePath = $disk->path($audio->path);
                $metadata = $this->metadataReader->read($absolutePath);

                $context->durationSeconds = $metadata->durationSeconds;
                $context->bitrate = $metadata->bitrate;
                $context->sampleRate = $metadata->sampleRate;
                $context->channels = $metadata->channels;
                $context->format = $metadata->format;

                $this->logger->completeStage($metaLog, 'Metadata extracted.', [
                    'duration' => $metadata->durationSeconds,
                    'format' => $metadata->format,
                    'codec' => $metadata->codec,
                ]);
            } catch (\Throwable $e) {
                $this->logger->failStage($metaLog, $e);
                throw $e;
            }

            // Step 7: Validate metadata
            $this->runStep($audio, $context, 'validated', function () use ($context): void {
                $this->validateMetadata($context);
            }, $jobId);

            // Step 8: Convert
            $convertLog = $this->logger->startStage($audio, 'converted', [], $jobId);

            try {
                $result = $this->conversionStep->execute($audio, $context);

                if (! $result->success && ! $result->skipped) {
                    $this->logger->failStage($convertLog, $result->message, $result->context);
                    throw new RuntimeException('Conversion failed: '.$result->message);
                }

                $this->logger->completeStage($convertLog, $result->message, $result->context);
            } catch (\Throwable $e) {
                if ($e instanceof \DomainException) {
                    throw $e;
                }
                $this->logger->failStage($convertLog, $e);
                throw $e;
            }

            // Step 9: Normalize
            $normalizeLog = $this->logger->startStage($audio, 'normalized', [], $jobId);

            try {
                $result = $this->normalizationStep->execute($audio, $context);

                if (! $result->success && ! $result->skipped) {
                    $this->logger->failStage($normalizeLog, $result->message, $result->context);
                    throw new RuntimeException('Normalization failed: '.$result->message);
                }

                if ($result->warning) {
                    $this->logger->completeStage($normalizeLog, $result->message, ['warning' => true]);
                } else {
                    $this->logger->completeStage($normalizeLog, $result->message, $result->context);
                }
            } catch (\Throwable $e) {
                $this->logger->failStage($normalizeLog, $e);
                throw $e;
            }

            // Step 10: Silence detection
            $silenceLog = $this->logger->startStage($audio, 'silence_detected', [], $jobId);

            try {
                $result = $this->silenceStep->execute($audio, $context);

                if (! $result->success && ! $result->skipped) {
                    $this->logger->failStage($silenceLog, $result->message);
                    // Not a hard failure — just warn
                    $context->addWarning('Silence detection failed: '.$result->message);
                    $this->logger->completeStage($silenceLog, 'Silence detection skipped due to error.');
                } else {
                    $this->logger->completeStage($silenceLog, $result->message, $result->context);
                }
            } catch (\Throwable $e) {
                $this->logger->failStage($silenceLog, $e);
                // Silence detection is not a hard failure
                $context->addWarning('Silence detection threw exception: '.$e->getMessage());
            }

            // Step 11: Waveform
            $waveformLog = $this->logger->startStage($audio, 'waveform_generated', [], $jobId);

            try {
                $result = $this->waveformStep->execute($audio, $context);

                if (! $result->success && ! $result->skipped) {
                    $this->logger->failStage($waveformLog, $result->message, $result->context);
                    throw new RuntimeException('Waveform generation failed: '.$result->message);
                }

                $this->logger->completeStage($waveformLog, $result->message, $result->context);
            } catch (\Throwable $e) {
                $this->logger->failStage($waveformLog, $e);
                throw $e;
            }

            // Step 12: Verify outputs
            $verifyLog = $this->logger->startStage($audio, 'files_verified', [], $jobId);

            try {
                $result = $this->verificationStep->execute($audio, $context);

                if (! $result->success) {
                    $this->logger->failStage($verifyLog, $result->message, $result->context);
                    throw new RuntimeException('File verification failed: '.$result->message);
                }

                $this->logger->completeStage($verifyLog, $result->message, $result->context);
            } catch (\Throwable $e) {
                $this->logger->failStage($verifyLog, $e);
                throw $e;
            }

            // Step 13: Complete
            $this->completePipeline($audio, $context);

            $this->logger->updateMetaHistory($audio, 'completed', 'completed', 'Pipeline completed successfully.', 0, [
                'pipeline_version' => $pipelineVersion,
                'warnings' => $context->warnings,
            ]);
        } catch (\Throwable $e) {
            $this->failPipeline($audio, $context, $e);
            throw $e;
        } finally {
            // Release lock
            if ($context->lockToken !== null) {
                $this->lockService->release($audio, $context->lockToken);
            }
        }
    }

    private function isAlreadyCompleted(ListeningAudio $audio): bool
    {
        return $audio->processing_status === ListeningAudioProcessingStatus::Completed;
    }

    private function markProcessing(ListeningAudio $audio, string $pipelineVersion): void
    {
        DB::table('listening_audios')
            ->where('id', $audio->id)
            ->update([
                'processing_status' => ListeningAudioProcessingStatus::Processing->value,
                'processing_started_at' => now(),
                'processing_error' => null,
                'pipeline_version' => $pipelineVersion,
                'updated_at' => now(),
            ]);

        $audio->forceFill([
            'processing_status' => ListeningAudioProcessingStatus::Processing,
            'processing_started_at' => now(),
            'pipeline_version' => $pipelineVersion,
        ]);
    }

    /**
     * Run a simple closure as a pipeline step, logging start/complete/fail.
     */
    private function runStep(
        ListeningAudio $audio,
        AudioPipelineContextData $context,
        string $stage,
        callable $fn,
        ?string $jobId = null,
    ): void {
        $log = $this->logger->startStage($audio, $stage, [], $jobId);

        try {
            $fn();
            $this->logger->completeStage($log, ucfirst($stage).' completed.');
        } catch (\Throwable $e) {
            $this->logger->failStage($log, $e);
            throw $e;
        }
    }

    private function validateMetadata(AudioPipelineContextData $context): void
    {
        $minDuration = (int) config('listening.audio.min_duration_seconds', 30);
        $maxDuration = (int) config('listening.audio.max_duration_seconds', 3600);

        if ($context->durationSeconds === null) {
            throw new \DomainException('Audio duration could not be determined.');
        }

        if ($context->durationSeconds < $minDuration) {
            throw new \DomainException(
                "Audio duration ({$context->durationSeconds}s) is below minimum ({$minDuration}s)."
            );
        }

        if ($context->durationSeconds > $maxDuration) {
            throw new \DomainException(
                "Audio duration ({$context->durationSeconds}s) exceeds maximum ({$maxDuration}s)."
            );
        }
    }

    private function completePipeline(ListeningAudio $audio, AudioPipelineContextData $context): void
    {
        $updates = [
            'processing_status' => ListeningAudioProcessingStatus::Completed->value,
            'validation_status' => ListeningAudioValidationStatus::Valid->value,
            'processing_finished_at' => now(),
            'last_processed_at' => now(),
            'processing_error' => null,
            'updated_at' => now(),
        ];

        if ($context->processedPath !== null) {
            $updates['processed_path'] = $context->processedPath;
        }

        if ($context->normalizedPath !== null) {
            $updates['normalized_path'] = $context->normalizedPath;
        }

        if ($context->waveformJsonPath !== null) {
            $updates['waveform_json_path'] = $context->waveformJsonPath;
        }

        if ($context->waveformPreviewPath !== null) {
            $updates['preview_waveform_path'] = $context->waveformPreviewPath;
            $updates['waveform_path'] = $context->waveformPreviewPath;
        }

        if (! empty($context->peaks)) {
            $updates['peaks'] = json_encode($context->peaks);
        }

        if ($context->loudnessLufs !== null) {
            $updates['loudness_lufs'] = $context->loudnessLufs;
        }

        if ($context->peakDb !== null) {
            $updates['peak_db'] = $context->peakDb;
        }

        if ($context->silenceReport !== null) {
            $updates['silence_report'] = json_encode($context->silenceReport);
        }

        if ($context->durationSeconds !== null) {
            $updates['duration_seconds'] = (int) round($context->durationSeconds);
        }

        if ($context->bitrate !== null) {
            $updates['bitrate'] = $context->bitrate;
        }

        if ($context->sampleRate !== null) {
            $updates['sample_rate'] = $context->sampleRate;
        }

        if ($context->channels !== null) {
            $updates['channels'] = $context->channels;
        }

        if ($context->format !== null) {
            $updates['format'] = $context->format;
        }

        DB::table('listening_audios')
            ->where('id', $audio->id)
            ->update($updates);

        $audio->forceFill([
            'processing_status' => ListeningAudioProcessingStatus::Completed,
            'validation_status' => ListeningAudioValidationStatus::Valid,
        ]);
    }

    private function failPipeline(ListeningAudio $audio, AudioPipelineContextData $context, \Throwable $e): void
    {
        // retry_count is managed by the action/controller layer, not the pipeline service.
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

        $this->logger->updateMetaHistory($audio, 'failed', 'failed', $e->getMessage());
    }
}
