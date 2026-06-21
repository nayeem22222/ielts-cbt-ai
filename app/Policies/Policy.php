<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class Policy
{
    protected function isOwner(User $user, Model $model, string $foreignKey = 'user_id'): bool
    {
        return (int) $user->getKey() === (int) $model->getAttribute($foreignKey);
    }
}
