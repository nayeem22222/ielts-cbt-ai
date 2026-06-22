<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Auth\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class QuestionBankPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::QuestionBanksView);
    }

    public function view(User $user, Model $model): bool
    {
        return $user->hasPermission(Permission::QuestionBanksView);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::QuestionBanksCreate);
    }

    public function update(User $user, Model $model): bool
    {
        return $user->hasPermission(Permission::QuestionBanksUpdate);
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->hasPermission(Permission::QuestionBanksDelete);
    }
}
