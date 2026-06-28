<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Auth\Permission;
use App\Models\Listening\ListeningAudio;
use App\Models\User;

class ListeningAudioPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ListeningAudiosView);
    }

    public function view(User $user, ListeningAudio $audio): bool
    {
        return $user->hasPermission(Permission::ListeningAudiosView);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ListeningAudiosCreate);
    }

    public function update(User $user, ListeningAudio $audio): bool
    {
        return $user->hasPermission(Permission::ListeningAudiosUpdate);
    }

    public function delete(User $user, ListeningAudio $audio): bool
    {
        return $user->hasPermission(Permission::ListeningAudiosDelete);
    }

    public function process(User $user, ListeningAudio $audio): bool
    {
        return $user->hasPermission(Permission::ListeningAudiosProcess);
    }

    public function retry(User $user, ListeningAudio $audio): bool
    {
        return $user->hasPermission(Permission::ListeningAudiosRetry);
    }

    public function generateWaveform(User $user, ListeningAudio $audio): bool
    {
        return $user->hasPermission(Permission::ListeningAudiosWaveform);
    }

    public function validateAudio(User $user, ListeningAudio $audio): bool
    {
        return $user->hasPermission(Permission::ListeningAudiosValidate);
    }
}
