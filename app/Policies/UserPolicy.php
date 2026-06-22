<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Auth\UserRole;
use App\Models\User;

class UserPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdminPanelUser();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdminPanelUser();
    }

    public function create(User $user): bool
    {
        return $user->isAdminPanelUser();
    }

    public function update(User $user, User $model): bool
    {
        if (! $user->isAdminPanelUser()) {
            return false;
        }

        if ($model->hasRole(UserRole::SuperAdmin) && ! $user->hasRole(UserRole::SuperAdmin)) {
            return false;
        }

        return true;
    }

    public function delete(User $user, User $model): bool
    {
        if (! $user->isAdminPanelUser()) {
            return false;
        }

        if ((int) $user->id === (int) $model->id) {
            return false;
        }

        if ($model->hasRole(UserRole::SuperAdmin) && ! $user->hasRole(UserRole::SuperAdmin)) {
            return false;
        }

        return true;
    }
}
