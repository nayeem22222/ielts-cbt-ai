<?php

declare(strict_types=1);

namespace App\Services\Listening\Result;

use App\Enums\Listening\ListeningAttemptStatus;
use App\Models\Listening\ListeningResult;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class ListeningResultAccessService
{
    public function __construct(
        private readonly ListeningResultVisibilityService $visibility,
    ) {}

    public function assertStudentCanView(ListeningResult $result, User $user): void
    {
        if (! $this->visibility->canStudentView($result, $user)) {
            throw new AuthorizationException('You are not allowed to view this result.');
        }

        $attempt = $result->attempt;

        if ($attempt !== null && $attempt->status === ListeningAttemptStatus::InProgress) {
            throw new AuthorizationException('Result is not available for in-progress attempts.');
        }
    }

    public function assertStudentCanViewAttempt(User $user, int $attemptUserId, ListeningAttemptStatus $status): void
    {
        if ((int) $user->id !== $attemptUserId) {
            throw new AuthorizationException('You are not allowed to view this result.');
        }

        if ($status === ListeningAttemptStatus::InProgress) {
            throw new AuthorizationException('Result is not available for in-progress attempts.');
        }
    }
}
