<?php

declare(strict_types=1);

namespace App\Policies\Listening;

use App\Enums\Auth\Permission;
use App\Enums\Auth\UserRole;
use App\Models\Listening\ListeningResult;
use App\Models\User;
use App\Policies\Policy;

class ListeningResultPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $this->isOwnerStudent($user) || $this->canAdminView($user);
    }

    public function view(User $user, ListeningResult $result): bool
    {
        if ($this->isOwner($user, $result)) {
            return $result->is_visible_to_student
                && $result->status?->value !== 'hidden';
        }

        return false;
    }

    public function viewAdmin(User $user, ListeningResult $result): bool
    {
        return $this->canAdminView($user);
    }

    public function viewAnyAdmin(User $user): bool
    {
        return $this->canAdminView($user);
    }

    public function publish(User $user, ListeningResult $result): bool
    {
        return $user->hasPermission(Permission::ListeningResultsPublish)
            || $this->hasAdminRole($user);
    }

    public function hide(User $user, ListeningResult $result): bool
    {
        return $user->hasPermission(Permission::ListeningResultsHide)
            || $this->hasAdminRole($user);
    }

    public function rebuild(User $user, ListeningResult $result): bool
    {
        return $user->hasPermission(Permission::ListeningResultsRebuild)
            || $this->hasAdminRole($user);
    }

    private function canAdminView(User $user): bool
    {
        return $user->hasPermission(Permission::ListeningResultsAdminView)
            || $this->hasAdminRole($user);
    }

    private function isOwnerStudent(User $user): bool
    {
        return $user->hasRole(UserRole::Student);
    }

    private function hasAdminRole(User $user): bool
    {
        return $user->hasRole(UserRole::Admin) || $user->hasRole(UserRole::SuperAdmin);
    }
}
