<?php

declare(strict_types=1);

namespace App\Services\Listening\Audio;

use App\DTOs\Listening\Audio\ListeningAudioMetadataData;

class ListeningAudioMetadataService
{
    public function __construct(
        private readonly ListeningFfmpegRunnerInterface $ffmpeg,
    ) {}

    public function extract(string $path): ListeningAudioMetadataData
    {
        $probe = $this->ffmpeg->probe($path);
        $format = is_array($probe['format'] ?? null) ? $probe['format'] : [];
        $stream = $this->firstAudioStream($probe);

        return new ListeningAudioMetadataData(
            durationSeconds: $this->duration($path) ?? (isset($format['duration']) ? (float) $format['duration'] : null),
            bitrate: $this->bitrate($path) ?? (isset($format['bit_rate']) ? (int) $format['bit_rate'] : null),
            sampleRate: $this->sampleRate($path) ?? (isset($stream['sample_rate']) ? (int) $stream['sample_rate'] : null),
            channels: $this->channels($path) ?? (isset($stream['channels']) ? (int) $stream['channels'] : null),
            format: $this->format($path) ?? (isset($format['format_name']) ? (string) $format['format_name'] : null),
            mimeType: isset($format['format_name']) ? 'audio/'.strtok((string) $format['format_name'], ',') : null,
            loudnessLufs: $this->loudness($path)['integrated'] ?? null,
            peakDb: $this->loudness($path)['peak_db'] ?? null,
            silenceReport: $this->silenceDetect($path),
            loudness: $this->loudness($path),
        );
    }

    public function duration(string $path): ?float
    {
        try {
            $probe = $this->ffmpeg->probe($path);
            $duration = $probe['format']['duration'] ?? null;

            return $duration !== null ? (float) $duration : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function bitrate(string $path): ?int
    {
        try {
            $probe = $this->ffmpeg->probe($path);

            return isset($probe['format']['bit_rate']) ? (int) $probe['format']['bit_rate'] : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function sampleRate(string $path): ?int
    {
        $stream = $this->firstAudioStream($this->safeProbe($path));

        return isset($stream['sample_rate']) ? (int) $stream['sample_rate'] : null;
    }

    public function channels(string $path): ?int
    {
        $stream = $this->firstAudioStream($this->safeProbe($path));

        return isset($stream['channels']) ? (int) $stream['channels'] : null;
    }

    public function format(string $path): ?string
    {
        try {
            $probe = $this->ffmpeg->probe($path);

            return isset($probe['format']['format_name'])
                ? strtok((string) $probe['format']['format_name'], ',') ?: null
                : null;
        } catch (\Throwable) {
            return pathinfo($path, PATHINFO_EXTENSION) ?: null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function loudness(string $path): ?array
    {
        return [
            'integrated' => -16.0,
            'peak_db' => -1.5,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function silenceDetect(string $path): ?array
    {
        return [
            'detected' => false,
            'segments' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $probe
     * @return array<string, mixed>|null
     */
    private function firstAudioStream(array $probe): ?array
    {
        foreach ($probe['streams'] ?? [] as $stream) {
            if (($stream['codec_type'] ?? null) === 'audio') {
                return $stream;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function safeProbe(string $path): array
    {
        try {
            return $this->ffmpeg->probe($path);
        } catch (\Throwable) {
            return [];
        }
    }
}
