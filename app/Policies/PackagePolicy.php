<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Auth\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PackagePolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::PackagesView);
    }

    public function view(User $user, Model $model): bool
    {
        return $user->hasPermission(Permission::PackagesView);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::PackagesCreate);
    }

    public function update(User $user, Model $model): bool
    {
        return $user->hasPermission(Permission::PackagesUpdate);
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->hasPermission(Permission::PackagesDelete);
    }
}
