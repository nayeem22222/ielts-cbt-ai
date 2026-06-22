<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Auth\Permission;
use App\Enums\Auth\UserRole;
use App\Models\User;

class UserPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::UsersView);
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermission(Permission::UsersView);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::UsersCreate);
    }

    public function update(User $user, User $model): bool
    {
        if (! $user->hasPermission(Permission::UsersUpdate)) {
            return false;
        }

        if ($model->hasRole(UserRole::SuperAdmin) && ! $user->hasRole(UserRole::SuperAdmin)) {
            return false;
        }

        return true;
    }

    public function delete(User $user, User $model): bool
    {
        if (! $user->hasPermission(Permission::UsersDelete)) {
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

    public function assignPermissions(User $user, User $model): bool
    {
        if (! $user->hasPermission(Permission::PermissionsAssign)) {
            return false;
        }

        if ($model->hasRole(UserRole::SuperAdmin) && ! $user->hasRole(UserRole::SuperAdmin)) {
            return false;
        }

        return true;
    }
}
