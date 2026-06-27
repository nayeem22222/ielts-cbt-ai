<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Auth\Permission;
use App\Models\Listening\ListeningTest;
use App\Models\User;

class ListeningTestPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ListeningTestsView);
    }

    public function view(User $user, ListeningTest $listeningTest): bool
    {
        return $user->hasPermission(Permission::ListeningTestsView);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ListeningTestsCreate);
    }

    public function update(User $user, ListeningTest $listeningTest): bool
    {
        return $user->hasPermission(Permission::ListeningTestsUpdate);
    }

    public function delete(User $user, ListeningTest $listeningTest): bool
    {
        return $user->hasPermission(Permission::ListeningTestsDelete);
    }

    public function restore(User $user, ListeningTest $listeningTest): bool
    {
        return $user->hasPermission(Permission::ListeningTestsDelete);
    }

    public function forceDelete(User $user, ListeningTest $listeningTest): bool
    {
        return $user->hasPermission(Permission::ListeningTestsDelete);
    }

    public function publish(User $user, ListeningTest $listeningTest): bool
    {
        return $user->hasPermission(Permission::ListeningTestsPublish);
    }

    public function archive(User $user, ListeningTest $listeningTest): bool
    {
        return $user->hasPermission(Permission::ListeningTestsArchive);
    }

    public function duplicate(User $user, ListeningTest $listeningTest): bool
    {
        return $user->hasPermission(Permission::ListeningTestsDuplicate);
    }
}
