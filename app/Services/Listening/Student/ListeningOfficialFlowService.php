<?php

declare(strict_types=1);

namespace App\Services\Listening\Student;

use App\DTOs\Listening\Student\ListeningPhaseStateData;
use App\Enums\Listening\ListeningAttemptPhase;
use App\Enums\Listening\ListeningAttemptStatus;
use App\Models\Listening\ListeningAttempt;

class ListeningOfficialFlowService
{
    public function __construct(
        private readonly ListeningOfficialTimerService $timer,
    ) {}

    public function initializeAttemptFlow(ListeningAttempt $attempt): void
    {
        // Timestamps are set by lifecycle service on start.
    }

    public function getPhaseState(ListeningAttempt $attempt): ListeningPhaseStateData
    {
        $phase = $this->timer->calculatePhase($attempt);

        return new ListeningPhaseStateData(
            currentPhase: $phase,
            currentPhaseLabel: $phase->label(),
            canNavigate: $this->canNavigate($attempt),
            canSaveAnswer: $this->canSaveAnswer($attempt),
            canPlayAudio: $this->canPlayAudio($attempt),
            canSubmit: $this->canSubmit($attempt),
            shouldEnterTransfer: $this->shouldEnterTransfer($attempt),
            shouldAutoSubmit: $this->shouldAutoSubmit($attempt),
        );
    }

    public function canNavigate(ListeningAttempt $attempt): bool
    {
        return $this->isActiveAttempt($attempt) && ! $this->timer->isExpired($attempt);
    }

    public function canSaveAnswer(ListeningAttempt $attempt): bool
    {
        return $this->timer->canSaveAnswer($attempt);
    }

    public function canPlayAudio(ListeningAttempt $attempt): bool
    {
        return $this->timer->canPlayAudio($attempt);
    }

    public function canSubmit(ListeningAttempt $attempt): bool
    {
        return $this->isActiveAttempt($attempt);
    }

    public function shouldEnterTransfer(ListeningAttempt $attempt): bool
    {
        return $this->timer->shouldEnterTransfer($attempt);
    }

    public function shouldAutoSubmit(ListeningAttempt $attempt): bool
    {
        return $this->timer->shouldAutoSubmit($attempt);
    }

    private function isActiveAttempt(ListeningAttempt $attempt): bool
    {
        return $attempt->status === ListeningAttemptStatus::InProgress;
    }
}
