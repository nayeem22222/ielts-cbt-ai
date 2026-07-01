<?php

declare(strict_types=1);

namespace App\Services\Listening\Review;

use App\Models\Listening\ListeningResult;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class ListeningReviewAccessService
{
    public function __construct(
        private readonly ListeningReviewVisibilityService $visibility,
    ) {}

    public function assertStudentCanReview(User $user, ListeningResult $result): void
    {
        if (! $this->visibility->canShowReview($result, $user, forAdmin: false)) {
            throw new AuthorizationException('You are not allowed to review this result.');
        }
    }

    public function assertAdminCanReview(User $user, ListeningResult $result): void
    {
        if ($result->status !== \App\Enums\Listening\ListeningResultStatus::Ready
            && $result->status !== \App\Enums\Listening\ListeningResultStatus::Hidden) {
            throw new AuthorizationException('Review is not available until the result is ready.');
        }
    }
}
