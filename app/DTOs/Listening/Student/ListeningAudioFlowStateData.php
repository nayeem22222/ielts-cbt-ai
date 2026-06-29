<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Student;

final readonly class ListeningAudioFlowStateData
{
    /**
     * @param  array<string, mixed>  $sections
     */
    public function __construct(
        public int $attemptId,
        public int $sectionNumber,
        public bool $canStart,
        public bool $canReplay,
        public bool $completed,
        public int $playCount,
        public array $sections,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'attempt_id' => $this->attemptId,
            'section_number' => $this->sectionNumber,
            'can_start' => $this->canStart,
            'can_replay' => $this->canReplay,
            'completed' => $this->completed,
            'play_count' => $this->playCount,
            'sections' => $this->sections,
        ];
    }
}
