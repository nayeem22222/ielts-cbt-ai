<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Audio\Pipeline;

final readonly class AudioPipelineStageResultData
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $stage,
        public bool $success,
        public string $message,
        public int $durationMs,
        public array $context = [],
        public bool $skipped = false,
        public bool $warning = false,
    ) {}

    public static function success(string $stage, string $message, int $durationMs = 0, array $context = []): self
    {
        return new self(
            stage: $stage,
            success: true,
            message: $message,
            durationMs: $durationMs,
            context: $context,
        );
    }

    public static function failure(string $stage, string $message, int $durationMs = 0, array $context = []): self
    {
        return new self(
            stage: $stage,
            success: false,
            message: $message,
            durationMs: $durationMs,
            context: $context,
        );
    }

    public static function skipped(string $stage, string $reason): self
    {
        return new self(
            stage: $stage,
            success: true,
            message: $reason,
            durationMs: 0,
            skipped: true,
        );
    }

    public static function warning(string $stage, string $message, array $context = []): self
    {
        return new self(
            stage: $stage,
            success: true,
            message: $message,
            durationMs: 0,
            context: $context,
            warning: true,
        );
    }
}
