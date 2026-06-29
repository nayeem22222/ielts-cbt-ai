<?php

declare(strict_types=1);

namespace App\Services\Listening\Student;

use App\Models\Listening\ListeningAttempt;

class ListeningTimerService
{
    public function totalSeconds(ListeningAttempt $attempt): int
    {
        $test = $attempt->test;

        $duration = (int) ($test?->duration_minutes ?? 0) * 60;
        $transfer = (int) ($test?->transfer_time_minutes ?? 0) * 60;

        return max(0, $duration + $transfer);
    }

    public function remainingSeconds(ListeningAttempt $attempt): int
    {
        if ($attempt->expires_at === null) {
            return $this->totalSeconds($attempt);
        }

        return max(0, (int) now()->diffInSeconds($attempt->expires_at, false));
    }

    public function isExpired(ListeningAttempt $attempt): bool
    {
        if ($attempt->expires_at === null) {
            return false;
        }

        return now()->greaterThanOrEqualTo($attempt->expires_at);
    }

    public function syncRemainingSeconds(ListeningAttempt $attempt): ListeningAttempt
    {
        $remaining = $this->remainingSeconds($attempt);

        if ((int) $attempt->remaining_seconds !== $remaining) {
            $attempt->forceFill(['remaining_seconds' => $remaining])->save();
        }

        return $attempt->refresh();
    }
}
