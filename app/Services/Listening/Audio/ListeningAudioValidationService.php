<?php

declare(strict_types=1);

namespace App\Services\Listening\Audio;

use App\DTOs\Listening\Audio\ListeningAudioValidationResultData;
use App\Enums\Listening\ListeningAudioValidationStatus;
use App\Models\Listening\ListeningAudio;
use App\Models\Listening\ListeningAudio as ListeningAudioModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ListeningAudioValidationService
{
    public function __construct(
        private readonly ListeningAudioStorageService $storage,
        private readonly ListeningFfmpegRunnerInterface $ffmpeg,
    ) {}

    public function validateFile(UploadedFile|string $file): ListeningAudioValidationResultData
    {
        $errors = [];

        if ($file instanceof UploadedFile) {
            if (! $file->isValid()) {
                return new ListeningAudioValidationResultData(ListeningAudioValidationStatus::Invalid, [[
                    'code' => 'upload_failed',
                    'message' => 'Audio upload failed.',
                ]]);
            }

            $errors = array_merge(
                $errors,
                $this->validateMime((string) $file->getMimeType()),
                $this->validateExtension(strtolower((string) $file->getClientOriginalExtension())),
                $this->validateFileSize((int) $file->getSize()),
            );
        } elseif (! is_file($file)) {
            $errors[] = [
                'code' => 'file_missing',
                'message' => 'Audio file does not exist.',
            ];
        }

        return new ListeningAudioValidationResultData(
            $errors === [] ? ListeningAudioValidationStatus::Valid : ListeningAudioValidationStatus::Invalid,
            $errors,
        );
    }

    /**
     * @return list<array{code: string, message: string}>
     */
    public function validateDuration(?float $duration): array
    {
        $errors = [];
        $min = (int) config('listening.audio.min_duration_seconds', 30);
        $max = (int) config('listening.audio.max_duration_seconds', 3600);

        if ($duration === null) {
            $errors[] = [
                'code' => 'duration_missing',
                'message' => 'Audio duration could not be determined.',
            ];

            return $errors;
        }

        if ($duration < $min) {
            $errors[] = [
                'code' => 'duration_too_short',
                'message' => 'Audio duration is shorter than minimum allowed duration.',
            ];
        }

        if ($duration > $max) {
            $errors[] = [
                'code' => 'duration_too_long',
                'message' => 'Audio duration exceeds maximum allowed duration.',
            ];
        }

        return $errors;
    }

    /**
     * @return list<array{code: string, message: string}>
     */
    public function validateMime(string $mime): array
    {
        $allowed = config('listening.audio.allowed_mimes', []);

        if ($allowed !== [] && ! in_array(strtolower($mime), array_map('strtolower', $allowed), true)) {
            return [[
                'code' => 'invalid_mime',
                'message' => 'Unsupported audio mime type.',
            ]];
        }

        return [];
    }

    /**
     * @return list<array{code: string, message: string}>
     */
    public function validateExtension(string $extension): array
    {
        $allowed = config('listening.audio.allowed_extensions', []);

        if ($allowed !== [] && ! in_array(strtolower($extension), $allowed, true)) {
            return [[
                'code' => 'invalid_extension',
                'message' => 'Unsupported audio file extension.',
            ]];
        }

        return [];
    }

    /**
     * @return list<array{code: string, message: string}>
     */
    public function validateFileSize(int $bytes): array
    {
        $maxMb = (int) config('listening.audio.max_file_size_mb', 100);
        $maxBytes = $maxMb * 1024 * 1024;

        if ($bytes > $maxBytes) {
            return [[
                'code' => 'file_too_large',
                'message' => "Audio file exceeds {$maxMb} MB limit.",
            ]];
        }

        return [];
    }

    /**
     * @return list<array{code: string, message: string}>
     */
    public function validateAudioIntegrity(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            return [[
                'code' => 'file_unreadable',
                'message' => 'Audio file is not readable.',
            ]];
        }

        if (! $this->ffmpeg->isFfprobeAvailable()) {
            return [[
                'code' => 'ffprobe_missing',
                'message' => 'FFprobe is not available.',
            ]];
        }

        try {
            $this->ffmpeg->probe($path);
        } catch (\Throwable $exception) {
            return [[
                'code' => 'corrupted_audio',
                'message' => 'Audio file appears to be corrupted or unsupported.',
            ]];
        }

        return [];
    }

    /**
     * @return list<array{code: string, message: string}>
     */
    public function validateForPublish(ListeningAudio $audio): array
    {
        $errors = [];

        if (! $this->storage->exists($audio)) {
            $errors[] = [
                'code' => 'file_missing',
                'message' => 'Processed audio file is missing.',
            ];
        }

        if ($audio->processing_status !== \App\Enums\Listening\ListeningAudioProcessingStatus::Completed) {
            $errors[] = [
                'code' => 'processing_incomplete',
                'message' => 'Audio processing is not completed.',
            ];
        }

        if ($audio->validation_status !== ListeningAudioValidationStatus::Valid) {
            $errors[] = [
                'code' => 'validation_invalid',
                'message' => 'Audio validation is not valid.',
            ];
        }

        if ($audio->duration_seconds === null || (int) $audio->duration_seconds <= 0) {
            $errors[] = [
                'code' => 'duration_missing',
                'message' => 'Audio duration is missing.',
            ];
        }

        if (config('listening.publishing.require_waveform', false) && blank($audio->waveform_json_path)) {
            $errors[] = [
                'code' => 'waveform_missing',
                'message' => 'Audio waveform is missing.',
            ];
        }

        return $errors;
    }
}
