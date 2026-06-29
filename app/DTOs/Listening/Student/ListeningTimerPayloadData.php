<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Student;

final readonly class ListeningTimerPayloadData
{
    public function __construct(
        public int $remainingSeconds,
        public int $totalSeconds,
        public string $serverNow,
        public string $expiresAt,
        public bool $isExpired,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'remaining_seconds' => $this->remainingSeconds,
            'total_seconds' => $this->totalSeconds,
            'server_now' => $this->serverNow,
            'expires_at' => $this->expiresAt,
            'is_expired' => $this->isExpired,
        ];
    }
}
