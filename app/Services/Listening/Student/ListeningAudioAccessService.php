<?php

declare(strict_types=1);

namespace App\Services\Listening\Student;

use App\Enums\Listening\ListeningAudioProcessingStatus;
use App\Enums\Listening\ListeningAudioValidationStatus;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningAudio;
use App\Models\Listening\ListeningSection;
use App\Services\Listening\Audio\ListeningAudioStorageService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListeningAudioAccessService
{
    public function __construct(
        private readonly ListeningAudioStorageService $storage,
    ) {}

    public function sectionHasPlayableAudio(ListeningSection $section): bool
    {
        $audio = $section->audio;

        if ($audio === null) {
            return false;
        }

        return $this->resolveStreamPath($audio) !== null;
    }

    public function audioIsPlayable(ListeningAudio $audio): bool
    {
        if ($audio->processing_status !== ListeningAudioProcessingStatus::Completed) {
            return false;
        }

        if ($audio->validation_status !== ListeningAudioValidationStatus::Valid) {
            return false;
        }

        $path = $audio->playablePath();

        if ($path === null) {
            return false;
        }

        return Storage::disk($audio->disk ?: $this->storage->disk())->exists($path);
    }

    public function resolveStreamPath(ListeningAudio $audio): ?string
    {
        $disk = Storage::disk($audio->disk ?: $this->storage->disk());

        foreach ([
            $audio->playablePath(),
            $audio->processed_path,
            $audio->path,
            $audio->normalized_path,
        ] as $path) {
            if (is_string($path) && $path !== '' && $disk->exists($path)) {
                return $path;
            }
        }

        return null;
    }

    public function resolveSection(ListeningAttempt $attempt, int $sectionNumber): ?ListeningSection
    {
        return $attempt->test?->sections()
            ->where('section_number', $sectionNumber)
            ->where('is_active', true)
            ->first();
    }

    public function streamSectionAudio(ListeningAttempt $attempt, int $sectionNumber): StreamedResponse
    {
        $section = $this->resolveSection($attempt, $sectionNumber);

        if ($section === null || $section->audio === null) {
            abort(404, 'Audio is not available for this section.');
        }

        $streamPath = $this->resolveStreamPath($section->audio);

        if ($streamPath === null) {
            abort(404, 'Audio is not available for this section.');
        }

        $audio = $section->audio;
        $disk = Storage::disk($audio->disk ?: $this->storage->disk());
        $absolutePath = $disk->path($streamPath);
        $mime = $audio->mime_type ?: $this->guessMimeType($streamPath);

        $headers = [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Content-Disposition' => 'inline',
        ];

        if (config('listening.audio_access.prevent_download_headers', true)) {
            $headers['Content-Disposition'] = 'inline; filename="section-'.$sectionNumber.'.mp3"';
        }

        return response()->stream(function () use ($absolutePath): void {
            $stream = fopen($absolutePath, 'rb');

            if ($stream === false) {
                return;
            }

            fpassthru($stream);
            fclose($stream);
        }, 200, $headers);
    }

    private function guessMimeType(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'm4a', 'aac' => 'audio/mp4',
            'webm' => 'audio/webm',
            default => 'audio/mpeg',
        };
    }
}
