<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Review;

final readonly class AudioTimestampReviewData
{
    public function __construct(
        public int $sectionNumber,
        public ?float $startSeconds,
        public ?float $endSeconds,
        public ?string $safeAudioUrl,
        public bool $enabled,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'section_number' => $this->sectionNumber,
            'start_seconds' => $this->startSeconds,
            'end_seconds' => $this->endSeconds,
            'safe_audio_url' => $this->safeAudioUrl,
            'enabled' => $this->enabled,
        ];
    }
}
