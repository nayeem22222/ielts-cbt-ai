<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Auth\AuthenticationEventType;
use App\Events\Auth\AuthenticationEvent;
use App\Models\AuthEventLog;
use App\Models\LoginLog;
use App\Models\User;

class AuthenticationEventLogger extends Service
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        AuthenticationEventType $type,
        ?User $user = null,
        ?string $email = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        array $metadata = [],
    ): AuthEventLog {
        $record = AuthEventLog::query()->create([
            'user_id' => $user?->id,
            'email' => $email ?? $user?->email,
            'event' => $type->value,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'metadata' => $metadata === [] ? null : $metadata,
            'created_at' => now(),
        ]);

        $this->mirrorLegacyLoginLog($type, $user, $email, $ipAddress, $userAgent, $metadata);

        return $record;
    }

    public function logEvent(AuthenticationEvent $event): AuthEventLog
    {
        return $this->log(
            type: $event->type(),
            user: $event->user,
            ipAddress: $event->ipAddress,
            userAgent: $event->userAgent,
            metadata: $event->metadata,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function mirrorLegacyLoginLog(
        AuthenticationEventType $type,
        ?User $user,
        ?string $email,
        ?string $ipAddress,
        ?string $userAgent,
        array $metadata,
    ): void {
        if ($type === AuthenticationEventType::UserLoggedIn) {
            LoginLog::query()->create([
                'user_id' => $user?->id,
                'email' => $email ?? $user?->email,
                'ip_address' => $ipAddress ?? '0.0.0.0',
                'user_agent' => $userAgent,
                'status' => 'success',
                'logged_in_at' => now(),
                'created_at' => now(),
            ]);

            return;
        }

        if ($type === AuthenticationEventType::LoginFailed) {
            LoginLog::query()->create([
                'user_id' => $user?->id,
                'email' => $email,
                'ip_address' => $ipAddress ?? '0.0.0.0',
                'user_agent' => $userAgent,
                'status' => 'failed',
                'failure_reason' => is_string($metadata['failure_reason'] ?? null)
                    ? $metadata['failure_reason']
                    : 'invalid_credentials',
                'created_at' => now(),
            ]);
        }
    }
}
