<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Auth\Permission;
use App\Enums\Auth\UserRole;
use App\Models\Role;
use App\Models\User;

class RolePolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::RolesView);
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasPermission(Permission::RolesView);
    }

    public function updatePermissions(User $user, Role $role): bool
    {
        if (! $user->hasPermission(Permission::RolesManagePermissions)) {
            return false;
        }

        if ($role->slug === UserRole::SuperAdmin->value && ! $user->hasRole(UserRole::SuperAdmin)) {
            return false;
        }

        return true;
    }

    public function assign(User $user): bool
    {
        return $user->hasPermission(Permission::RolesAssign);
    }
}
