<?php

declare(strict_types=1);

namespace App\Services\Listening\Audio;

use App\Models\Listening\ListeningAudio;
use Illuminate\Support\Facades\Storage;

class ListeningAudioStorageService
{
    public function disk(): string
    {
        return (string) config('listening.audio.disk', 'public');
    }

    public function path(ListeningAudio $audio): string
    {
        $disk = Storage::disk($audio->disk ?: $this->disk());

        foreach ([
            $audio->playablePath(),
            $audio->normalized_path,
            $audio->processed_path,
            $audio->path,
        ] as $path) {
            if (is_string($path) && $path !== '' && $disk->exists($path)) {
                return $path;
            }
        }

        return (string) ($audio->path ?: $audio->normalized_path ?: $audio->processed_path);
    }

    public function absolutePath(ListeningAudio $audio): string
    {
        return Storage::disk($audio->disk ?: $this->disk())->path($this->path($audio));
    }

    public function originalAbsolutePath(ListeningAudio $audio): string
    {
        return Storage::disk($audio->disk ?: $this->disk())->path((string) $audio->path);
    }

    public function url(ListeningAudio $audio): ?string
    {
        $disk = Storage::disk($audio->disk ?: $this->disk());

        foreach ([
            $audio->playablePath(),
            $audio->normalized_path,
            $audio->processed_path,
            $audio->path,
        ] as $path) {
            if (is_string($path) && $path !== '' && $disk->exists($path)) {
                return $disk->url($path);
            }
        }

        return null;
    }

    public function exists(ListeningAudio $audio): bool
    {
        return Storage::disk($audio->disk ?: $this->disk())->exists($this->path($audio));
    }

    public function deleteFiles(ListeningAudio $audio): void
    {
        $disk = Storage::disk($audio->disk ?: $this->disk());

        foreach ([
            $audio->path,
            $audio->processed_path,
            $audio->normalized_path,
            $audio->waveform_path,
            $audio->waveform_json_path,
            $audio->preview_waveform_path,
        ] as $path) {
            if (is_string($path) && $path !== '' && $disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }

    public function moveToProcessed(ListeningAudio $audio, string $sourceAbsolutePath): string
    {
        $disk = Storage::disk($audio->disk ?: $this->disk());
        $directory = trim((string) config('listening.audio.directories.processed', 'listening/audio/processed'), '/');
        $filename = pathinfo((string) $audio->stored_name, PATHINFO_FILENAME).'.'.config('listening.audio.target_format', 'mp3');
        $relativePath = $directory.'/'.$filename;

        $disk->put($relativePath, file_get_contents($sourceAbsolutePath) ?: '');

        return $relativePath;
    }

    public function storeNormalizedCopy(ListeningAudio $audio, string $sourceAbsolutePath): string
    {
        $disk = Storage::disk($audio->disk ?: $this->disk());
        $directory = trim((string) config('listening.audio.directories.normalized', 'listening/audio/normalized'), '/');
        $filename = pathinfo((string) $audio->stored_name, PATHINFO_FILENAME).'-normalized.'.config('listening.audio.target_format', 'mp3');
        $relativePath = $directory.'/'.$filename;

        $disk->put($relativePath, file_get_contents($sourceAbsolutePath) ?: '');

        return $relativePath;
    }

    public function directory(string $key): string
    {
        return trim((string) config("listening.audio.directories.{$key}", "listening/audio/{$key}"), '/');
    }
}
