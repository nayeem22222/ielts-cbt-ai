<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Student;

use App\Enums\Listening\ListeningAttemptPhase;
use App\Enums\Listening\ListeningAttemptStatus;

final readonly class ListeningTimerStateData
{
    public function __construct(
        public int $attemptId,
        public ListeningAttemptStatus $status,
        public ListeningAttemptPhase $currentPhase,
        public string $serverNow,
        public ?string $timerStartedAt,
        public ?string $listeningStartedAt,
        public ?string $listeningEndedAt,
        public ?string $transferStartedAt,
        public ?string $transferEndedAt,
        public ?string $expiresAt,
        public int $listeningRemainingSeconds,
        public int $transferRemainingSeconds,
        public int $totalRemainingSeconds,
        public bool $isExpired,
        public bool $shouldEnterTransfer,
        public bool $shouldAutoSubmit,
        public bool $canSaveAnswer,
        public bool $canPlayAudio,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'attempt_id' => $this->attemptId,
            'status' => $this->status->value,
            'current_phase' => $this->currentPhase->value,
            'current_phase_label' => $this->currentPhase->label(),
            'server_now' => $this->serverNow,
            'timer_started_at' => $this->timerStartedAt,
            'listening_started_at' => $this->listeningStartedAt,
            'listening_ended_at' => $this->listeningEndedAt,
            'transfer_started_at' => $this->transferStartedAt,
            'transfer_ended_at' => $this->transferEndedAt,
            'expires_at' => $this->expiresAt,
            'listening_remaining_seconds' => $this->listeningRemainingSeconds,
            'transfer_remaining_seconds' => $this->transferRemainingSeconds,
            'total_remaining_seconds' => $this->totalRemainingSeconds,
            'remaining_seconds' => $this->totalRemainingSeconds,
            'is_expired' => $this->isExpired,
            'should_enter_transfer' => $this->shouldEnterTransfer,
            'should_auto_submit' => $this->shouldAutoSubmit,
            'can_save_answer' => $this->canSaveAnswer,
            'can_play_audio' => $this->canPlayAudio,
        ];
    }
}
