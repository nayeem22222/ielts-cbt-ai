<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Auth\Permission;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Models\User;

class ListeningSectionPolicy extends Policy
{
    public function viewAny(User $user, ListeningTest $listeningTest): bool
    {
        return $user->hasPermission(Permission::ListeningSectionsView)
            || $user->hasPermission(Permission::ListeningTestsView);
    }

    public function view(User $user, ListeningSection $section): bool
    {
        return $user->hasPermission(Permission::ListeningSectionsView)
            || $user->hasPermission(Permission::ListeningTestsView);
    }

    public function create(User $user, ListeningTest $listeningTest): bool
    {
        return $user->hasPermission(Permission::ListeningSectionsCreate)
            || $user->hasPermission(Permission::ListeningTestsUpdate);
    }

    public function update(User $user, ListeningSection $section): bool
    {
        return $user->hasPermission(Permission::ListeningSectionsUpdate)
            || $user->hasPermission(Permission::ListeningTestsUpdate);
    }

    public function delete(User $user, ListeningSection $section): bool
    {
        return $user->hasPermission(Permission::ListeningSectionsDelete)
            || $user->hasPermission(Permission::ListeningTestsUpdate);
    }

    public function restore(User $user, ListeningSection $section): bool
    {
        return $user->hasPermission(Permission::ListeningSectionsRestore)
            || $user->hasPermission(Permission::ListeningTestsUpdate);
    }

    public function reorder(User $user, ListeningTest $listeningTest): bool
    {
        return $user->hasPermission(Permission::ListeningSectionsReorder)
            || $user->hasPermission(Permission::ListeningTestsUpdate);
    }

    public function createDefault(User $user, ListeningTest $listeningTest): bool
    {
        return $user->hasPermission(Permission::ListeningSectionsCreate)
            || $user->hasPermission(Permission::ListeningTestsUpdate);
    }
}
