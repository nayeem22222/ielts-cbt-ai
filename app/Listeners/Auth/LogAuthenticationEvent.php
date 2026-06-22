<?php

declare(strict_types=1);

namespace App\Listeners\Auth;

use App\Events\Auth\AuthenticationEvent;
use App\Services\AuthenticationEventLogger;

class LogAuthenticationEvent
{
    public function __construct(private readonly AuthenticationEventLogger $logger)
    {
    }

    public function handle(AuthenticationEvent $event): void
    {
        $this->logger->logEvent($event);
    }
}
