<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Audio\Pipeline;

final readonly class FfprobeMetadataResultData
{
    /**
     * @param  array<string, mixed>  $streams
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public ?float $durationSeconds,
        public ?int $bitrate,
        public ?int $sampleRate,
        public ?int $channels,
        public ?string $format,
        public ?string $codec,
        public array $streams,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $json
     */
    public static function fromFfprobe(array $json): self
    {
        $format = is_array($json['format'] ?? null) ? $json['format'] : [];
        $streams = is_array($json['streams'] ?? null) ? $json['streams'] : [];
        $audioStream = self::firstAudioStream($streams);

        $duration = null;

        if (isset($audioStream['duration']) && is_numeric($audioStream['duration'])) {
            $duration = (float) $audioStream['duration'];
        } elseif (isset($format['duration']) && is_numeric($format['duration'])) {
            $duration = (float) $format['duration'];
        }

        $bitrate = null;

        if (isset($audioStream['bit_rate']) && is_numeric($audioStream['bit_rate'])) {
            $bitrate = (int) $audioStream['bit_rate'];
        } elseif (isset($format['bit_rate']) && is_numeric($format['bit_rate'])) {
            $bitrate = (int) $format['bit_rate'];
        }

        $formatName = null;

        if (isset($format['format_name'])) {
            $formatName = strtok((string) $format['format_name'], ',') ?: null;
        }

        return new self(
            durationSeconds: $duration,
            bitrate: $bitrate,
            sampleRate: isset($audioStream['sample_rate']) ? (int) $audioStream['sample_rate'] : null,
            channels: isset($audioStream['channels']) ? (int) $audioStream['channels'] : null,
            format: $formatName,
            codec: isset($audioStream['codec_name']) ? (string) $audioStream['codec_name'] : null,
            streams: $streams,
            raw: $json,
        );
    }

    /**
     * @param  array<string, mixed>  $streams
     * @return array<string, mixed>|null
     */
    private static function firstAudioStream(array $streams): ?array
    {
        foreach ($streams as $stream) {
            if (($stream['codec_type'] ?? null) === 'audio') {
                return $stream;
            }
        }

        return null;
    }

    public function hasDuration(): bool
    {
        return $this->durationSeconds !== null && $this->durationSeconds > 0;
    }
}
