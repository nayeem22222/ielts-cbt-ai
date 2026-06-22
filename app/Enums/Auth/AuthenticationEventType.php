<?php

declare(strict_types=1);

namespace App\Enums\Auth;

use App\Enums\Concerns\EnumHelpers;

enum AuthenticationEventType: string
{
    use EnumHelpers;

    case UserRegistered = 'user_registered';
    case UserLoggedIn = 'user_logged_in';
    case UserLoggedOut = 'user_logged_out';
    case PasswordChanged = 'password_changed';
    case LoginFailed = 'login_failed';
}
