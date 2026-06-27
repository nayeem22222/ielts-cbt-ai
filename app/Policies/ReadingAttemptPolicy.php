<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Exam\TestAttemptStatus;
use App\Models\ReadingAttempt;
use App\Models\User;

class ReadingAttemptPolicy
{
    public function view(User $user, ReadingAttempt $attempt): bool
    {
        return $user->id === $attempt->user_id || $user->can('tests.view');
    }

    public function update(User $user, ReadingAttempt $attempt): bool
    {
        return $user->id === $attempt->user_id
            && $attempt->status === TestAttemptStatus::InProgress;
    }

    public function submit(User $user, ReadingAttempt $attempt): bool
    {
        return $this->update($user, $attempt);
    }
}
