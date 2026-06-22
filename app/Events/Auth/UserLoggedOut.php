<?php

declare(strict_types=1);

namespace App\Events\Auth;

use App\Enums\Auth\AuthenticationEventType;

class UserLoggedOut extends AuthenticationEvent
{
    public function type(): AuthenticationEventType
    {
        return AuthenticationEventType::UserLoggedOut;
    }
}
