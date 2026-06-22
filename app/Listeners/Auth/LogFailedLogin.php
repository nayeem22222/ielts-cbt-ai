<?php

declare(strict_types=1);

namespace App\Listeners\Auth;

use App\Enums\Auth\AuthenticationEventType;
use App\Services\AuthenticationEventLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;

class LogFailedLogin
{
    public function __construct(
        private readonly Request $request,
        private readonly AuthenticationEventLogger $logger,
    ) {
    }

    public function handle(Failed $event): void
    {
        $email = is_array($event->credentials) ? ($event->credentials['email'] ?? null) : null;

        $this->logger->log(
            type: AuthenticationEventType::LoginFailed,
            user: $event->user,
            email: is_string($email) ? $email : null,
            ipAddress: $this->request->ip() ?? '0.0.0.0',
            userAgent: $this->request->userAgent(),
            metadata: [
                'failure_reason' => 'invalid_credentials',
            ],
        );
    }
}
