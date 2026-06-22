<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\Auth\PasswordChanged;
use App\Events\Auth\UserLoggedIn;
use App\Events\Auth\UserLoggedOut;
use App\Events\Auth\UserRegistered;
use App\Listeners\Auth\LogAuthenticationEvent;
use App\Listeners\Auth\LogFailedLogin;
use Illuminate\Auth\Events\Failed;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        UserRegistered::class => [
            LogAuthenticationEvent::class,
        ],
        UserLoggedIn::class => [
            LogAuthenticationEvent::class,
        ],
        UserLoggedOut::class => [
            LogAuthenticationEvent::class,
        ],
        PasswordChanged::class => [
            LogAuthenticationEvent::class,
        ],
        Failed::class => [
            LogFailedLogin::class,
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
