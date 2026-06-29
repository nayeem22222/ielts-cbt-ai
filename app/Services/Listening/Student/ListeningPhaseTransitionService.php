<?php

declare(strict_types=1);

namespace App\Services\Listening\Student;

use App\Enums\Listening\ListeningAttemptPhase;
use App\Enums\Listening\ListeningAttemptStatus;
use App\Models\Listening\ListeningAttempt;
use App\Repositories\Listening\Student\ListeningAttemptRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ListeningPhaseTransitionService
{
    public function __construct(
        private readonly ListeningAttemptRepository $attempts,
        private readonly ListeningOfficialTimerService $timer,
    ) {}

    public function transition(ListeningAttempt $attempt, ListeningAttemptPhase $targetPhase): ListeningAttempt
    {
        $errors = $this->validateTransition($attempt, $targetPhase);

        if ($errors !== []) {
            throw ValidationException::withMessages(['phase' => implode(' ', $errors)]);
        }

        return DB::transaction(function () use ($attempt, $targetPhase): ListeningAttempt {
            return match ($targetPhase) {
                ListeningAttemptPhase::Listening => $this->transitionToListening($attempt),
                ListeningAttemptPhase::Transfer => $this->transitionToTransfer($attempt),
                ListeningAttemptPhase::Submitting => $this->transitionToSubmitting($attempt),
                ListeningAttemptPhase::Submitted => $this->transitionToSubmitted($attempt),
                ListeningAttemptPhase::Expired => $this->transitionToExpired($attempt),
                default => $attempt,
            };
        });
    }

    public function transitionToListening(ListeningAttempt $attempt): ListeningAttempt
    {
        return $this->transition($attempt, ListeningAttemptPhase::Listening);
    }

    public function transitionToTransfer(ListeningAttempt $attempt): ListeningAttempt
    {
        return $this->attempts->update($attempt, [
            'current_phase' => ListeningAttemptPhase::Transfer,
            'transfer_started_at' => $attempt->transfer_started_at ?? now(),
        ]);
    }

    public function transitionToSubmitting(ListeningAttempt $attempt): ListeningAttempt
    {
        return $this->attempts->update($attempt, [
            'current_phase' => ListeningAttemptPhase::Submitting,
        ]);
    }

    public function transitionToSubmitted(ListeningAttempt $attempt): ListeningAttempt
    {
        return $this->attempts->update($attempt, [
            'current_phase' => ListeningAttemptPhase::Submitted,
            'status' => ListeningAttemptStatus::Submitted,
            'submitted_at' => $attempt->submitted_at ?? now(),
            'remaining_seconds' => 0,
        ]);
    }

    public function transitionToExpired(ListeningAttempt $attempt): ListeningAttempt
    {
        return $this->attempts->update($attempt, [
            'current_phase' => ListeningAttemptPhase::Expired,
            'remaining_seconds' => 0,
        ]);
    }

    /**
     * @return list<string>
     */
    public function validateTransition(ListeningAttempt $attempt, ListeningAttemptPhase $targetPhase): array
    {
        $current = $attempt->current_phase instanceof ListeningAttemptPhase
            ? $attempt->current_phase
            : (ListeningAttemptPhase::tryFrom((string) $attempt->current_phase) ?? ListeningAttemptPhase::Listening);

        if (in_array($current, [ListeningAttemptPhase::Submitted, ListeningAttemptPhase::Expired], true)
            && in_array($targetPhase, [ListeningAttemptPhase::Listening, ListeningAttemptPhase::Transfer], true)) {
            return ['Cannot roll back to an earlier phase after submission or expiry.'];
        }

        $allowed = match ($current) {
            ListeningAttemptPhase::Instructions => [ListeningAttemptPhase::Listening],
            ListeningAttemptPhase::Listening => [
                ListeningAttemptPhase::Transfer,
                ListeningAttemptPhase::Submitting,
                ListeningAttemptPhase::Expired,
            ],
            ListeningAttemptPhase::Transfer => [
                ListeningAttemptPhase::Submitting,
                ListeningAttemptPhase::Expired,
            ],
            ListeningAttemptPhase::Submitting => [ListeningAttemptPhase::Submitted],
            default => [],
        };

        if ($targetPhase === $current) {
            return [];
        }

        if (! in_array($targetPhase, $allowed, true)) {
            return ["Transition from {$current->value} to {$targetPhase->value} is not allowed."];
        }

        if ($targetPhase === ListeningAttemptPhase::Transfer && ! $this->timer->isListeningTimeEnded($attempt)) {
            return ['Listening time has not ended yet.'];
        }

        return [];
    }
}
