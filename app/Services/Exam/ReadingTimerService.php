<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Enums\Exam\TestAttemptStatus;
use App\Models\ReadingAttempt;
use Carbon\CarbonInterface;
use App\Support\Reading\ReadingSecurityLogger;
use Illuminate\Support\Carbon;

class ReadingTimerService
{
    public function durationSeconds(ReadingAttempt $attempt): int
    {
        $attempt->loadMissing('test');

        return max(0, (int) $attempt->test?->duration_minutes) * 60;
    }

    public function startedAt(ReadingAttempt $attempt): CarbonInterface
    {
        return $attempt->started_at ?? $attempt->created_at ?? now();
    }

    public function endsAt(ReadingAttempt $attempt): CarbonInterface
    {
        return $this->startedAt($attempt)->copy()->addSeconds($this->durationSeconds($attempt));
    }

    public function remainingSeconds(ReadingAttempt $attempt): int
    {
        if ($attempt->status !== TestAttemptStatus::InProgress) {
            return max(0, (int) $attempt->remaining_seconds);
        }

        $remaining = $this->endsAt($attempt)->getTimestamp() - now()->getTimestamp();

        return max(0, $remaining);
    }

    public function isExpired(ReadingAttempt $attempt): bool
    {
        return $attempt->status === TestAttemptStatus::InProgress
            && $this->remainingSeconds($attempt) <= 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function syncTimer(ReadingAttempt $attempt): array
    {
        $remaining = $this->remainingSeconds($attempt);

        if ($attempt->status === TestAttemptStatus::InProgress) {
            $stored = (int) $attempt->remaining_seconds;
            if (abs($stored - $remaining) >= 5) {
                $attempt->update(['remaining_seconds' => $remaining]);
            }
        }

        return $this->timerSyncPayload($attempt, $remaining);
    }

    /**
     * @return array<string, mixed>
     */
    public function timerSyncPayload(ReadingAttempt $attempt, ?int $remaining = null): array
    {
        $remaining ??= $this->remainingSeconds($attempt);

        return [
            'remaining_seconds' => $remaining,
            'status' => $attempt->status?->value ?? TestAttemptStatus::InProgress->value,
            'server_time' => now()->toIso8601String(),
            'expired' => $remaining <= 0 && $attempt->status === TestAttemptStatus::InProgress,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function timerPayload(?ReadingAttempt $attempt = null, ?int $remaining = null): array
    {
        $attempt ??= new ReadingAttempt;
        $remaining ??= $this->remainingSeconds($attempt);

        return array_merge($this->timerSyncPayload($attempt, $remaining), [
            'ends_at' => $this->endsAt($attempt)->toIso8601String(),
            'duration_seconds' => $this->durationSeconds($attempt),
        ]);
    }

    public function assertOwnedByUser(ReadingAttempt $attempt, ?int $userId = null): void
    {
        $userId ??= auth()->id();

        if ($userId === null || $attempt->user_id !== $userId) {
            ReadingSecurityLogger::ownershipDenied('attempt_access', $userId, $attempt);
            abort(403);
        }
    }
}
