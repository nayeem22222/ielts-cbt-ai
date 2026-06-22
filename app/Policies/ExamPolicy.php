<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Auth\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ExamPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::TestsView);
    }

    public function view(User $user, Model $model): bool
    {
        return $user->hasPermission(Permission::TestsView);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::TestsCreate);
    }

    public function update(User $user, Model $model): bool
    {
        return $user->hasPermission(Permission::TestsUpdate);
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->hasPermission(Permission::TestsDelete);
    }
}
