<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Student;

final readonly class PlayerRecoveryData
{
    /**
     * @param  list<array<string, mixed>>  $unsavedAnswers
     * @param  array<string, mixed>  $serverSnapshot
     */
    public function __construct(
        public bool $hasUnsaved,
        public int $unsavedCount,
        public array $unsavedAnswers,
        public array $serverSnapshot,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'has_unsaved' => $this->hasUnsaved,
            'unsaved_count' => $this->unsavedCount,
            'unsaved_answers' => $this->unsavedAnswers,
            'server_snapshot' => $this->serverSnapshot,
        ];
    }
}
