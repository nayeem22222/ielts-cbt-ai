<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Student;

use App\Enums\Listening\ListeningAttemptPhase;

final readonly class ListeningPhaseStateData
{
    public function __construct(
        public ListeningAttemptPhase $currentPhase,
        public string $currentPhaseLabel,
        public bool $canNavigate,
        public bool $canSaveAnswer,
        public bool $canPlayAudio,
        public bool $canSubmit,
        public bool $shouldEnterTransfer,
        public bool $shouldAutoSubmit,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'current_phase' => $this->currentPhase->value,
            'current_phase_label' => $this->currentPhaseLabel,
            'can_navigate' => $this->canNavigate,
            'can_save_answer' => $this->canSaveAnswer,
            'can_play_audio' => $this->canPlayAudio,
            'can_submit' => $this->canSubmit,
            'should_enter_transfer' => $this->shouldEnterTransfer,
            'should_auto_submit' => $this->shouldAutoSubmit,
        ];
    }
}
