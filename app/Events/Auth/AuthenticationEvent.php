<?php

declare(strict_types=1);

namespace App\Events\Auth;

use App\Enums\Auth\AuthenticationEventType;
use App\Events\Event;
use App\Models\User;

abstract class AuthenticationEvent extends Event
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly User $user,
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
        public readonly array $metadata = [],
    ) {
    }

    abstract public function type(): AuthenticationEventType;
}
