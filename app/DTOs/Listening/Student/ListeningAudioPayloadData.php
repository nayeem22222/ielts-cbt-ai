<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Student;

final readonly class ListeningAudioPayloadData
{
    public function __construct(
        public int $sectionNumber,
        public string $streamUrl,
        public bool $allowReplay,
        public bool $allowSeek,
        public bool $allowSpeedChange,
        public ?int $preparationSeconds,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'section_number' => $this->sectionNumber,
            'stream_url' => $this->streamUrl,
            'allow_replay' => $this->allowReplay,
            'allow_seek' => $this->allowSeek,
            'allow_speed_change' => $this->allowSpeedChange,
            'preparation_seconds' => $this->preparationSeconds,
        ];
    }
}
