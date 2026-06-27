<?php

declare(strict_types=1);

namespace App\Support\Reading;

use App\Models\ReadingAttempt;
use Illuminate\Support\Facades\Log;

final class ReadingSecurityLogger
{
    public static function ownershipDenied(string $action, ?int $userId, ?ReadingAttempt $attempt = null): void
    {
        Log::warning('reading.security.ownership_denied', [
            'action' => $action,
            'user_id' => $userId,
            'attempt_id' => $attempt?->id,
            'attempt_uuid' => $attempt?->uuid,
        ]);
    }

    public static function invalidAnswerSave(string $reason, ?int $userId, ?ReadingAttempt $attempt = null): void
    {
        Log::warning('reading.security.invalid_answer_save', [
            'reason' => $reason,
            'user_id' => $userId,
            'attempt_id' => $attempt?->id,
        ]);
    }

    public static function repeatedSubmit(?int $userId, ?ReadingAttempt $attempt = null): void
    {
        Log::warning('reading.security.repeated_submit', [
            'user_id' => $userId,
            'attempt_id' => $attempt?->id,
            'attempt_uuid' => $attempt?->uuid,
        ]);
    }

    public static function evaluationFailed(?int $attemptId, string $reason): void
    {
        Log::error('reading.security.evaluation_failed', [
            'attempt_id' => $attemptId,
            'reason' => $reason,
        ]);
    }

    public static function autoSubmitFailed(?int $userId, ?ReadingAttempt $attempt = null): void
    {
        Log::warning('reading.security.auto_submit_failed', [
            'user_id' => $userId,
            'attempt_id' => $attempt?->id,
        ]);
    }
}
