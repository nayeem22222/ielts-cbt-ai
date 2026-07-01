<?php

declare(strict_types=1);

namespace App\Policies\Listening;

use App\Enums\Auth\Permission;
use App\Enums\Auth\UserRole;
use App\Enums\Listening\ListeningResultStatus;
use App\Models\Listening\ListeningResult;
use App\Models\User;
use App\Policies\Policy;
use App\Services\Listening\Review\ListeningReviewVisibilityService;

class ListeningReviewPolicy extends Policy
{
    public function viewStudent(User $user, ListeningResult $result): bool
    {
        return app(ListeningReviewVisibilityService::class)->canShowReview($result, $user, forAdmin: false)
            && ($user->hasPermission(Permission::ListeningReviewView) || $user->hasRole(UserRole::Student));
    }

    public function viewAdmin(User $user, ListeningResult $result): bool
    {
        return in_array($result->status, [ListeningResultStatus::Ready, ListeningResultStatus::Hidden], true)
            && ($user->hasPermission(Permission::ListeningReviewAdminView) || $this->hasAdminRole($user));
    }

    public function rebuild(User $user, ListeningResult $result): bool
    {
        return $user->hasPermission(Permission::ListeningReviewRebuild) || $this->hasAdminRole($user);
    }

    public function viewTranscript(User $user, ListeningResult $result): bool
    {
        if ($this->hasAdminRole($user)) {
            return true;
        }

        return $user->hasPermission(Permission::ListeningReviewTranscript)
            && app(ListeningReviewVisibilityService::class)->canShowTranscriptHighlight($result, forAdmin: false);
    }

    public function viewAudioReview(User $user, ListeningResult $result): bool
    {
        if ($this->hasAdminRole($user)) {
            return true;
        }

        return $user->hasPermission(Permission::ListeningReviewAudio)
            && app(ListeningReviewVisibilityService::class)->canShowAudioReview($result, forAdmin: false);
    }

    private function hasAdminRole(User $user): bool
    {
        return $user->hasRole(UserRole::Admin) || $user->hasRole(UserRole::SuperAdmin);
    }
}
