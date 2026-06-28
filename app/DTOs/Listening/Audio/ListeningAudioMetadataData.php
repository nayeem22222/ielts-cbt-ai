<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Audio;

final readonly class ListeningAudioMetadataData
{
    /**
     * @param  array<string, mixed>|null  $silenceReport
     * @param  array<string, mixed>|null  $loudness
     */
    public function __construct(
        public ?float $durationSeconds = null,
        public ?int $bitrate = null,
        public ?int $sampleRate = null,
        public ?int $channels = null,
        public ?string $format = null,
        public ?string $mimeType = null,
        public ?float $loudnessLufs = null,
        public ?float $peakDb = null,
        public ?array $silenceReport = null,
        public ?array $loudness = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'duration_seconds' => $this->durationSeconds !== null ? (int) round($this->durationSeconds) : null,
            'bitrate' => $this->bitrate,
            'sample_rate' => $this->sampleRate,
            'channels' => $this->channels,
            'format' => $this->format,
            'mime_type' => $this->mimeType,
            'loudness_lufs' => $this->loudnessLufs,
            'peak_db' => $this->peakDb,
            'silence_report' => $this->silenceReport,
        ], fn ($value) => $value !== null);
    }
}
