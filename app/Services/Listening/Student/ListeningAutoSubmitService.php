<?php

declare(strict_types=1);

namespace App\Services\Listening\Student;

use App\Enums\Listening\ListeningAttemptPhase;
use App\Enums\Listening\ListeningAttemptStatus;
use App\Models\Listening\ListeningAttempt;
use App\Repositories\Listening\Student\ListeningAttemptRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListeningAutoSubmitService
{
    public function __construct(
        private readonly ListeningAttemptRepository $attempts,
        private readonly ListeningOfficialTimerService $timer,
        private readonly ListeningPhaseTransitionService $phases,
    ) {}

    public function autoSubmitIfExpired(ListeningAttempt $attempt): bool
    {
        if (! $this->timer->isExpired($attempt)) {
            return false;
        }

        if ($attempt->status !== ListeningAttemptStatus::InProgress) {
            return false;
        }

        $this->autoSubmit($attempt);

        return true;
    }

    public function autoSubmit(ListeningAttempt $attempt, string $reason = 'timer_expired'): ListeningAttempt
    {
        if (! in_array($attempt->status, [ListeningAttemptStatus::InProgress], true)) {
            return $attempt;
        }

        return DB::transaction(function () use ($attempt, $reason): ListeningAttempt {
            $fresh = $attempt->refresh();

            if ($fresh->status !== ListeningAttemptStatus::InProgress) {
                return $fresh;
            }

            $this->phases->transitionToSubmitting($fresh);

            $meta = is_array($fresh->timer_meta) ? $fresh->timer_meta : [];
            $meta['auto_submit'] = [
                'reason' => $reason,
                'at' => now()->toIso8601String(),
            ];

            return $this->attempts->update($fresh, [
                'status' => ListeningAttemptStatus::AutoSubmitted,
                'current_phase' => ListeningAttemptPhase::Submitted,
                'submitted_at' => now(),
                'auto_submitted_at' => now(),
                'remaining_seconds' => 0,
                'timer_meta' => $meta,
            ]);
        });
    }

    /**
     * @return Collection<int, ListeningAttempt>
     */
    public function findExpiredInProgressAttempts(int $limit = 100): Collection
    {
        return ListeningAttempt::query()
            ->where('status', ListeningAttemptStatus::InProgress)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->orderBy('expires_at')
            ->limit($limit)
            ->get();
    }

    public function bulkAutoSubmitExpired(int $limit = 100): int
    {
        $count = 0;

        foreach ($this->findExpiredInProgressAttempts($limit) as $attempt) {
            $this->autoSubmit($attempt);
            $count++;
        }

        return $count;
    }
}
