<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Auth\Permission;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Models\User;

class ListeningQuestionPolicy extends Policy
{
    public function viewAny(User $user, ListeningQuestionGroup $group): bool
    {
        return $user->hasPermission(Permission::ListeningQuestionsView)
            || $user->hasPermission(Permission::ListeningTestsView);
    }

    public function view(User $user, ListeningQuestion $question): bool
    {
        return $user->hasPermission(Permission::ListeningQuestionsView)
            || $user->hasPermission(Permission::ListeningTestsView);
    }

    public function create(User $user, ListeningQuestionGroup $group): bool
    {
        return $user->hasPermission(Permission::ListeningQuestionsCreate)
            || $user->hasPermission(Permission::ListeningTestsUpdate);
    }

    public function update(User $user, ListeningQuestion $question): bool
    {
        return $user->hasPermission(Permission::ListeningQuestionsUpdate)
            || $user->hasPermission(Permission::ListeningTestsUpdate);
    }

    public function delete(User $user, ListeningQuestion $question): bool
    {
        return $user->hasPermission(Permission::ListeningQuestionsDelete)
            || $user->hasPermission(Permission::ListeningTestsUpdate);
    }

    public function bulkCreate(User $user, ListeningQuestionGroup $group): bool
    {
        return $user->hasPermission(Permission::ListeningQuestionsBulkCreate)
            || $user->hasPermission(Permission::ListeningTestsUpdate);
    }

    public function reorder(User $user, ListeningQuestionGroup $group): bool
    {
        return $user->hasPermission(Permission::ListeningQuestionsReorder)
            || $user->hasPermission(Permission::ListeningTestsUpdate);
    }
}
