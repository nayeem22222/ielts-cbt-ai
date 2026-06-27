<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Auth\Permission;
use App\Models\Listening\ListeningTranscript;
use App\Models\User;

class ListeningTranscriptPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ListeningTranscriptsView)
            || $user->hasPermission(Permission::ListeningTestsView);
    }

    public function view(User $user, ListeningTranscript $transcript): bool
    {
        return $user->hasPermission(Permission::ListeningTranscriptsView)
            || $user->hasPermission(Permission::ListeningTestsView);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ListeningTranscriptsCreate)
            || $user->hasPermission(Permission::ListeningTestsUpdate);
    }

    public function update(User $user, ListeningTranscript $transcript): bool
    {
        return $user->hasPermission(Permission::ListeningTranscriptsUpdate)
            || $user->hasPermission(Permission::ListeningTestsUpdate);
    }

    public function delete(User $user, ListeningTranscript $transcript): bool
    {
        return $user->hasPermission(Permission::ListeningTranscriptsDelete)
            || $user->hasPermission(Permission::ListeningTestsUpdate);
    }

    public function attachToSection(User $user, ListeningTranscript $transcript): bool
    {
        return $user->hasPermission(Permission::ListeningTranscriptsAttach)
            || $user->hasPermission(Permission::ListeningSectionsUpdate)
            || $user->hasPermission(Permission::ListeningTestsUpdate);
    }

    public function detachFromSection(User $user): bool
    {
        return $user->hasPermission(Permission::ListeningTranscriptsAttach)
            || $user->hasPermission(Permission::ListeningSectionsUpdate)
            || $user->hasPermission(Permission::ListeningTestsUpdate);
    }

    public function updateTimestamps(User $user, ListeningTranscript $transcript): bool
    {
        return $user->hasPermission(Permission::ListeningTranscriptsTimestampsUpdate)
            || $user->hasPermission(Permission::ListeningTranscriptsUpdate)
            || $user->hasPermission(Permission::ListeningTestsUpdate);
    }

    public function forceAttach(User $user): bool
    {
        return $user->hasPermission(Permission::ListeningTranscriptsAttach)
            || $user->hasPermission(Permission::ListeningTestsUpdate);
    }
}
