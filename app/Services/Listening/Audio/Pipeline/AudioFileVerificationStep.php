<?php

declare(strict_types=1);

namespace App\Services\Listening\Audio\Pipeline;

use App\DTOs\Listening\Audio\Pipeline\AudioPipelineContextData;
use App\DTOs\Listening\Audio\Pipeline\AudioPipelineStageResultData;
use App\Models\Listening\ListeningAudio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AudioFileVerificationStep
{
    public function execute(
        ListeningAudio $audio,
        AudioPipelineContextData $context,
    ): AudioPipelineStageResultData {
        $startMs = (int) round(microtime(true) * 1000);
        $disk = Storage::disk((string) config('listening.audio.disk', 'public'));
        $errors = [];

        // Verify original
        if (! $disk->exists($audio->path)) {
            $errors[] = 'Original file missing.';
        }

        // Verify processed if conversion ran
        if ($context->processedPath !== null) {
            $absProcessed = $disk->path($context->processedPath);

            if (! is_file($absProcessed) || filesize($absProcessed) === 0) {
                $errors[] = 'Processed file is missing or empty.';
            }
        }

        // Verify normalized if normalization ran
        if ($context->normalizedPath !== null) {
            $absNormalized = $disk->path($context->normalizedPath);

            if (! is_file($absNormalized) || filesize($absNormalized) === 0) {
                $errors[] = 'Normalized file is missing or empty.';
            }
        }

        // Verify waveform if generated
        if ($context->waveformJsonPath !== null) {
            $absWaveform = $disk->path($context->waveformJsonPath);

            if (! is_file($absWaveform)) {
                $errors[] = 'Waveform JSON file is missing.';
            }
        }

        // Determine final playable path
        $playablePath = $context->normalizedPath ?? $context->processedPath;

        if ($playablePath !== null) {
            $absPlayable = $disk->path($playablePath);

            if (! is_file($absPlayable) || filesize($absPlayable) === 0) {
                $errors[] = 'Final playable file is missing or empty.';
                $playablePath = null;
            }
        }

        if (! empty($errors)) {
            return AudioPipelineStageResultData::failure(
                stage: 'files_verified',
                message: 'File verification failed: '.implode('; ', $errors),
                durationMs: $this->elapsed($startMs),
                context: ['errors' => $errors],
            );
        }

        // Verify duration
        if ($context->durationSeconds === null || $context->durationSeconds <= 0) {
            return AudioPipelineStageResultData::failure(
                stage: 'files_verified',
                message: 'Duration is missing or zero.',
                durationMs: $this->elapsed($startMs),
            );
        }

        // Persist playable_path into meta
        if ($playablePath !== null) {
            $meta = is_array($audio->meta) ? $audio->meta : [];
            $meta['audio'] = array_merge(is_array($meta['audio'] ?? null) ? $meta['audio'] : [], [
                'playable_path' => $playablePath,
            ]);
            $meta['pipeline'] = array_merge(is_array($meta['pipeline'] ?? null) ? $meta['pipeline'] : [], [
                'version_path' => $context->versionTag,
            ]);

            DB::table('listening_audios')
                ->where('id', $audio->id)
                ->update(['meta' => json_encode($meta), 'updated_at' => now()]);

            $audio->forceFill(['meta' => $meta]);
        }

        return AudioPipelineStageResultData::success(
            stage: 'files_verified',
            message: 'All required files verified.',
            durationMs: $this->elapsed($startMs),
            context: [
                'playable_path' => $playablePath,
                'duration_seconds' => $context->durationSeconds,
            ],
        );
    }

    private function elapsed(int $startMs): int
    {
        return (int) round(microtime(true) * 1000) - $startMs;
    }
}
