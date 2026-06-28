<?php

declare(strict_types=1);

namespace App\Services\Listening\Audio;

use App\DTOs\Listening\Audio\ListeningWaveformData;
use App\Models\Listening\ListeningAudio;
use Illuminate\Support\Facades\Storage;

class ListeningWaveformService
{
    public function __construct(
        private readonly ListeningAudioStorageService $storage,
        private readonly ListeningFfmpegRunnerInterface $ffmpeg,
    ) {}

    public function generate(ListeningAudio $audio): ListeningWaveformData
    {
        $samples = (int) config('listening.audio.waveform_samples', 1000);
        $absolutePath = $this->storage->absolutePath($audio);
        $quality = 'full';

        try {
            $peaks = $this->generatePeaks($absolutePath, $samples);
        } catch (\Throwable) {
            $peaks = $this->generatePeaks($this->storage->originalAbsolutePath($audio), $samples);
            $quality = 'basic';
        }

        $duration = (float) ($audio->duration_seconds ?? 0);
        $waveform = new ListeningWaveformData(
            version: 1,
            samples: count($peaks),
            durationSeconds: $duration,
            peaks: $peaks,
            normalized: true,
            generatedAt: now()->toIso8601String(),
            quality: $quality,
        );

        $waveform = new ListeningWaveformData(
            version: $waveform->version,
            samples: $waveform->samples,
            durationSeconds: $waveform->durationSeconds,
            peaks: $waveform->peaks,
            normalized: $waveform->normalized,
            generatedAt: $waveform->generatedAt,
            jsonPath: $this->saveWaveformJson($audio, $waveform->peaks, $waveform),
            previewPath: $this->savePreviewImage($audio, $waveform->peaks),
            quality: $waveform->quality,
        );

        return $waveform;
    }

    /**
     * @return list<float>
     */
    public function generatePeaks(string $path, int $samples = 1000): array
    {
        return $this->ffmpeg->extractPeaks($path, $samples);
    }

    /**
     * @param  list<float>  $peaks
     */
    public function saveWaveformJson(ListeningAudio $audio, array $peaks, ?ListeningWaveformData $waveform = null): string
    {
        $disk = Storage::disk($audio->disk ?: $this->storage->disk());
        $directory = $this->storage->directory('waveforms');
        $filename = pathinfo((string) $audio->stored_name, PATHINFO_FILENAME).'.json';
        $relativePath = $directory.'/'.$filename;

        $document = ($waveform ?? new ListeningWaveformData(
            version: 1,
            samples: count($peaks),
            durationSeconds: (float) ($audio->duration_seconds ?? 0),
            peaks: $peaks,
            normalized: true,
            generatedAt: now()->toIso8601String(),
        ))->toJsonDocument();

        $disk->put($relativePath, json_encode($document, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return $relativePath;
    }

    /**
     * @param  list<float>  $peaks
     */
    public function savePreviewImage(ListeningAudio $audio, array $peaks): ?string
    {
        try {
            $disk = Storage::disk($audio->disk ?: $this->storage->disk());
            $directory = $this->storage->directory('previews');
            $filename = pathinfo((string) $audio->stored_name, PATHINFO_FILENAME).'.svg';
            $relativePath = $directory.'/'.$filename;
            $width = 800;
            $height = 120;
            $barWidth = max(1, (int) floor($width / max(1, count($peaks))));
            $bars = '';

            foreach (array_slice($peaks, 0, min(count($peaks), $width)) as $index => $peak) {
                $barHeight = max(2, (int) round($peak * ($height - 8)));
                $x = $index * $barWidth;
                $y = $height - $barHeight;
                $bars .= sprintf('<rect x="%d" y="%d" width="%d" height="%d" fill="#2563eb" rx="1"/>', $x, $y, max(1, $barWidth - 1), $barHeight);
            }

            $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
  <rect width="100%" height="100%" fill="#f8fafc"/>
  {$bars}
</svg>
SVG;

            $disk->put($relativePath, $svg);

            return $relativePath;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function loadWaveform(ListeningAudio $audio): ?array
    {
        if (blank($audio->waveform_json_path)) {
            return null;
        }

        $disk = Storage::disk($audio->disk ?: $this->storage->disk());

        if (! $disk->exists((string) $audio->waveform_json_path)) {
            return null;
        }

        $decoded = json_decode($disk->get((string) $audio->waveform_json_path), true);

        return is_array($decoded) ? $decoded : null;
    }

    public function deleteWaveformFiles(ListeningAudio $audio): void
    {
        $disk = Storage::disk($audio->disk ?: $this->storage->disk());

        foreach ([$audio->waveform_path, $audio->waveform_json_path, $audio->preview_waveform_path] as $path) {
            if (is_string($path) && $path !== '' && $disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }
}
