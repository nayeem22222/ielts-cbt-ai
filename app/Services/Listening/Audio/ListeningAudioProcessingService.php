<?php

declare(strict_types=1);

namespace App\Services\Listening\Audio;

use App\Enums\Listening\ListeningAudioProcessingStatus;
use App\Enums\Listening\ListeningAudioValidationStatus;
use App\Models\Listening\ListeningAudio;
use App\Repositories\Listening\ListeningAudioRepository;
use Illuminate\Support\Facades\DB;
use Throwable;

class ListeningAudioProcessingService
{
    public function __construct(
        private readonly ListeningAudioRepository $audios,
        private readonly ListeningAudioStorageService $storage,
        private readonly ListeningAudioValidationService $validation,
        private readonly ListeningAudioMetadataService $metadata,
        private readonly ListeningWaveformService $waveforms,
        private readonly ListeningFfmpegRunnerInterface $ffmpeg,
    ) {}

    public function process(ListeningAudio $audio): void
    {
        if (! $this->ffmpeg->isFfmpegAvailable() || ! $this->ffmpeg->isFfprobeAvailable()) {
            $this->markFailed($audio, 'FFmpeg is not available. Install FFmpeg or configure FFMPEG_BINARY / FFPROBE_BINARY.');

            return;
        }

        $this->markProcessing($audio);

        $tempFiles = [];

        try {
            $sourcePath = $this->storage->originalAbsolutePath($audio);
            $integrityErrors = $this->validation->validateAudioIntegrity($sourcePath);

            if ($integrityErrors !== []) {
                $this->markFailed($audio, $integrityErrors[0]['message'], $integrityErrors);

                return;
            }

            $metadata = $this->metadata->extract($sourcePath);
            $durationErrors = $this->validation->validateDuration($metadata->durationSeconds);

            if ($durationErrors !== []) {
                $this->markFailed($audio, $durationErrors[0]['message'], $durationErrors);

                return;
            }

            $processedTemp = sys_get_temp_dir().'/listening-processed-'.$audio->id.'-'.uniqid('', true).'.mp3';
            $tempFiles[] = $processedTemp;
            $this->convert($audio, $sourcePath, $processedTemp);
            $processedPath = $this->storage->moveToProcessed($audio, $processedTemp);

            $normalizedPath = null;

            if (config('listening.audio.normalize_audio', true)) {
                $normalizedTemp = sys_get_temp_dir().'/listening-normalized-'.$audio->id.'-'.uniqid('', true).'.mp3';
                $tempFiles[] = $normalizedTemp;
                $this->normalize($audio, $processedTemp, $normalizedTemp);
                $normalizedPath = $this->storage->storeNormalizedCopy($audio, $normalizedTemp);
            }

            $audio->refresh();
            $audio->forceFill(array_merge($metadata->toArray(), [
                'processed_path' => $processedPath,
                'normalized_path' => $normalizedPath,
                'format' => config('listening.audio.target_format', 'mp3'),
            ]))->save();

            if (config('listening.audio.generate_waveform', true)) {
                $waveform = $this->waveforms->generate($audio->refresh());
                $audio->forceFill([
                    'waveform_json_path' => $waveform->jsonPath,
                    'preview_waveform_path' => $waveform->previewPath,
                    'waveform_path' => $waveform->previewPath,
                    'peaks' => $waveform->peaks,
                    'meta' => array_merge($audio->meta ?? [], [
                        'waveform_quality' => $waveform->quality,
                    ]),
                ])->save();
            }

            $this->markCompleted($audio, [
                'validation_status' => ListeningAudioValidationStatus::Valid,
                'validation_errors' => null,
            ]);
        } catch (Throwable $exception) {
            $this->markFailed($audio, $exception->getMessage());
        } finally {
            $this->cleanupTemporaryFiles($tempFiles);
        }
    }

    public function markProcessing(ListeningAudio $audio): void
    {
        DB::transaction(function () use ($audio): void {
            $this->audios->update($audio, [
                'processing_status' => ListeningAudioProcessingStatus::Processing,
                'processing_started_at' => now(),
                'processing_finished_at' => null,
                'processing_error' => null,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function markCompleted(ListeningAudio $audio, array $payload = []): void
    {
        DB::transaction(function () use ($audio, $payload): void {
            $this->audios->update($audio, array_merge([
                'processing_status' => ListeningAudioProcessingStatus::Completed,
                'processing_finished_at' => now(),
                'processing_error' => null,
            ], $payload));
        });
    }

    /**
     * @param  list<array{code: string, message: string}>|null  $validationErrors
     */
    public function markFailed(ListeningAudio $audio, Throwable|string $error, ?array $validationErrors = null): void
    {
        $message = $error instanceof Throwable ? $error->getMessage() : $error;

        DB::transaction(function () use ($audio, $message, $validationErrors): void {
            $this->audios->update($audio, [
                'processing_status' => ListeningAudioProcessingStatus::Failed,
                'validation_status' => ListeningAudioValidationStatus::Invalid,
                'validation_errors' => $validationErrors ?? [[
                    'code' => 'processing_failed',
                    'message' => $message,
                ]],
                'processing_finished_at' => now(),
                'processing_error' => $message,
            ]);
        });
    }

    public function convert(ListeningAudio $audio, string $sourcePath, string $outputPath): string
    {
        $this->ffmpeg->convert($sourcePath, $outputPath);

        return $outputPath;
    }

    public function normalize(ListeningAudio $audio, string $sourcePath, string $outputPath): ?string
    {
        $this->ffmpeg->normalize(
            $sourcePath,
            $outputPath,
            (float) config('listening.audio.target_loudness_lufs', -16),
        );

        return $outputPath;
    }

    /**
     * @param  list<string>  $tempFiles
     */
    public function cleanupTemporaryFiles(array $tempFiles): void
    {
        foreach ($tempFiles as $file) {
            if (is_string($file) && is_file($file)) {
                @unlink($file);
            }
        }
    }
}
