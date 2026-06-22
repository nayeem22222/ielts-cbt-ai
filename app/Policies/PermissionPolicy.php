<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Auth\Permission;
use App\Models\Permission as PermissionModel;
use App\Models\User;

class PermissionPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::PermissionsView);
    }

    public function view(User $user, PermissionModel $permission): bool
    {
        return $user->hasPermission(Permission::PermissionsView);
    }

    public function assign(User $user): bool
    {
        return $user->hasPermission(Permission::PermissionsAssign);
    }
}
