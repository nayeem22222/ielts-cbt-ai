<?php

declare(strict_types=1);

namespace App\Services\Listening\Student;

use App\DTOs\Listening\Student\ListeningTimerStateData;
use App\Enums\Listening\ListeningAttemptPhase;
use App\Enums\Listening\ListeningAttemptStatus;
use App\Models\Listening\ListeningAttempt;
use App\Repositories\Listening\Student\ListeningAttemptRepository;

class ListeningOfficialTimerService
{
    public function __construct(
        private readonly ListeningAttemptRepository $attempts,
    ) {}

    public function startTimer(ListeningAttempt $attempt): ListeningTimerStateData
    {
        $now = now();

        $this->attempts->update($attempt, [
            'timer_started_at' => $attempt->timer_started_at ?? $now,
            'last_timer_sync_at' => $now,
        ]);

        return $this->getState($attempt->refresh());
    }

    /**
     * @param  array<string, mixed>  $clientState
     */
    public function sync(ListeningAttempt $attempt, array $clientState = []): ListeningTimerStateData
    {
        $meta = is_array($attempt->timer_meta) ? $attempt->timer_meta : [];
        $meta['last_client_sync'] = [
            'received_at' => now()->toIso8601String(),
            'client_remaining' => $clientState['client_remaining_seconds'] ?? null,
            'client_phase' => $clientState['client_phase'] ?? null,
        ];

        $this->attempts->update($attempt, [
            'last_timer_sync_at' => now(),
            'remaining_seconds' => $this->getTotalRemainingSeconds($attempt),
            'timer_meta' => $meta,
        ]);

        return $this->getState($attempt->refresh());
    }

    public function getState(ListeningAttempt $attempt): ListeningTimerStateData
    {
        $phase = $this->calculatePhase($attempt);

        return new ListeningTimerStateData(
            attemptId: (int) $attempt->id,
            status: $attempt->status,
            currentPhase: $phase,
            serverNow: now()->toIso8601String(),
            timerStartedAt: $attempt->timer_started_at?->toIso8601String(),
            listeningStartedAt: $attempt->listening_started_at?->toIso8601String(),
            listeningEndedAt: $attempt->listening_ended_at?->toIso8601String(),
            transferStartedAt: $attempt->transfer_started_at?->toIso8601String(),
            transferEndedAt: $attempt->transfer_ended_at?->toIso8601String(),
            expiresAt: $attempt->expires_at?->toIso8601String(),
            listeningRemainingSeconds: $this->getListeningRemainingSeconds($attempt),
            transferRemainingSeconds: $this->getTransferRemainingSeconds($attempt),
            totalRemainingSeconds: $this->getTotalRemainingSeconds($attempt),
            isExpired: $this->isExpired($attempt),
            shouldEnterTransfer: $this->shouldEnterTransfer($attempt),
            shouldAutoSubmit: $this->shouldAutoSubmit($attempt),
            canSaveAnswer: $this->canSaveAnswer($attempt, $phase),
            canPlayAudio: $this->canPlayAudio($attempt, $phase),
        );
    }

    public function getListeningRemainingSeconds(ListeningAttempt $attempt): int
    {
        if ($attempt->listening_ended_at === null) {
            return max(0, $this->getTotalRemainingSeconds($attempt));
        }

        return max(0, (int) now()->diffInSeconds($attempt->listening_ended_at, false));
    }

    public function getTransferRemainingSeconds(ListeningAttempt $attempt): int
    {
        if (! $this->hasTransferTime($attempt)) {
            return 0;
        }

        if ($attempt->transfer_ended_at === null) {
            return 0;
        }

        if ($this->isListeningTimeEnded($attempt) === false) {
            return max(0, (int) $attempt->transfer_started_at?->diffInSeconds($attempt->transfer_ended_at, false) ?? 0);
        }

        return max(0, (int) now()->diffInSeconds($attempt->transfer_ended_at, false));
    }

    public function getTotalRemainingSeconds(ListeningAttempt $attempt): int
    {
        if ($attempt->expires_at === null) {
            return $this->fallbackTotalSeconds($attempt);
        }

        return max(0, (int) now()->diffInSeconds($attempt->expires_at, false));
    }

    public function isListeningTimeEnded(ListeningAttempt $attempt): bool
    {
        if ($attempt->listening_ended_at === null) {
            return false;
        }

        return now()->greaterThanOrEqualTo($attempt->listening_ended_at);
    }

    public function isTransferTimeEnded(ListeningAttempt $attempt): bool
    {
        if (! $this->hasTransferTime($attempt)) {
            return $this->isListeningTimeEnded($attempt);
        }

        if ($attempt->transfer_ended_at === null) {
            return false;
        }

        return now()->greaterThanOrEqualTo($attempt->transfer_ended_at);
    }

    public function isExpired(ListeningAttempt $attempt): bool
    {
        if ($attempt->expires_at === null) {
            return false;
        }

        return now()->greaterThanOrEqualTo($attempt->expires_at);
    }

    public function calculatePhase(ListeningAttempt $attempt): ListeningAttemptPhase
    {
        if ($attempt->current_phase instanceof ListeningAttemptPhase) {
            if (in_array($attempt->current_phase, [
                ListeningAttemptPhase::Submitted,
                ListeningAttemptPhase::Expired,
                ListeningAttemptPhase::Submitting,
                ListeningAttemptPhase::Transfer,
            ], true)) {
                return $attempt->current_phase;
            }
        } elseif (is_string($attempt->current_phase)) {
            $stored = ListeningAttemptPhase::tryFrom($attempt->current_phase);

            if ($stored !== null && in_array($stored, [
                ListeningAttemptPhase::Submitted,
                ListeningAttemptPhase::Expired,
                ListeningAttemptPhase::Submitting,
                ListeningAttemptPhase::Transfer,
            ], true)) {
                return $stored;
            }
        }

        if ($this->isExpired($attempt)) {
            return ListeningAttemptPhase::Expired;
        }

        if ($this->isListeningTimeEnded($attempt) && $this->hasTransferTime($attempt) && ! $this->isTransferTimeEnded($attempt)) {
            return ListeningAttemptPhase::Transfer;
        }

        return ListeningAttemptPhase::Listening;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function updateTimerMeta(ListeningAttempt $attempt, array $meta): void
    {
        $existing = is_array($attempt->timer_meta) ? $attempt->timer_meta : [];
        $this->attempts->update($attempt, ['timer_meta' => array_merge($existing, $meta)]);
    }

    private function hasTransferTime(ListeningAttempt $attempt): bool
    {
        if (! config('listening.official_flow.allow_transfer_time', true)) {
            return false;
        }

        return $attempt->transfer_started_at !== null && $attempt->transfer_ended_at !== null;
    }

    private function fallbackTotalSeconds(ListeningAttempt $attempt): int
    {
        $test = $attempt->test;
        $duration = (int) ($test?->duration_minutes ?? config('listening.official_flow.default_listening_minutes', 30)) * 60;
        $transfer = $this->hasTransferTime($attempt)
            ? (int) ($test?->transfer_time_minutes ?? config('listening.official_flow.default_transfer_minutes', 10)) * 60
            : 0;

        return max(0, $duration + $transfer);
    }

    public function shouldEnterTransfer(ListeningAttempt $attempt): bool
    {
        if (! config('listening.official_flow.auto_enter_transfer_phase', true)) {
            return false;
        }

        if (! config('listening.official_flow.allow_transfer_time', true) || $attempt->transfer_started_at === null) {
            return false;
        }

        $phase = $attempt->current_phase instanceof ListeningAttemptPhase
            ? $attempt->current_phase
            : ListeningAttemptPhase::tryFrom((string) $attempt->current_phase);

        if ($phase === ListeningAttemptPhase::Transfer) {
            return false;
        }

        return $this->isListeningTimeEnded($attempt) && ! $this->isTransferTimeEnded($attempt);
    }

    public function shouldAutoSubmit(ListeningAttempt $attempt): bool
    {
        if (! config('listening.official_flow.auto_submit_on_expiry', true)) {
            return false;
        }

        return $attempt->status === ListeningAttemptStatus::InProgress && $this->isExpired($attempt);
    }

    public function canSaveAnswer(ListeningAttempt $attempt, ?ListeningAttemptPhase $phase = null): bool
    {
        if ($attempt->status !== ListeningAttemptStatus::InProgress || $this->isExpired($attempt)) {
            return false;
        }

        $phase ??= $this->calculatePhase($attempt);

        if ($phase === ListeningAttemptPhase::Listening) {
            return true;
        }

        if ($phase === ListeningAttemptPhase::Transfer) {
            return (bool) config('listening.official_flow.allow_answer_edit_during_transfer', true);
        }

        return false;
    }

    public function canPlayAudio(ListeningAttempt $attempt, ?ListeningAttemptPhase $phase = null): bool
    {
        if ($attempt->status !== ListeningAttemptStatus::InProgress || $this->isExpired($attempt)) {
            return false;
        }

        $phase ??= $this->calculatePhase($attempt);

        if (config('listening.official_audio.allow_audio_only_in_listening_phase', true)) {
            return $phase === ListeningAttemptPhase::Listening;
        }

        return true;
    }
}
