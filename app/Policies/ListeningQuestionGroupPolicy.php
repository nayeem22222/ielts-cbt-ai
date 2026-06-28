<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Auth\Permission;
use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Models\User;

class ListeningQuestionGroupPolicy extends Policy
{
    public function viewAny(User $user, ListeningTest $listeningTest, ListeningSection $section): bool
    {
        return $user->hasPermission(Permission::ListeningQuestionGroupsView)
            || $user->hasPermission(Permission::ListeningTestsView);
    }

    public function view(User $user, ListeningQuestionGroup $group): bool
    {
        return $user->hasPermission(Permission::ListeningQuestionGroupsView)
            || $user->hasPermission(Permission::ListeningTestsView);
    }

    public function create(User $user, ListeningTest $listeningTest, ListeningSection $section): bool
    {
        return $user->hasPermission(Permission::ListeningQuestionGroupsCreate)
            || $user->hasPermission(Permission::ListeningTestsUpdate);
    }

    public function update(User $user, ListeningQuestionGroup $group): bool
    {
        return $user->hasPermission(Permission::ListeningQuestionGroupsUpdate)
            || $user->hasPermission(Permission::ListeningTestsUpdate);
    }

    public function delete(User $user, ListeningQuestionGroup $group): bool
    {
        return $user->hasPermission(Permission::ListeningQuestionGroupsDelete)
            || $user->hasPermission(Permission::ListeningTestsUpdate);
    }
}
