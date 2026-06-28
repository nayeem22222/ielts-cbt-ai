<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Audio\Pipeline;

final readonly class FfmpegCommandResultData
{
    public function __construct(
        public int $exitCode,
        public bool $successful,
        public string $output,
        public string $errorOutput,
        public int $durationMs,
        public string $commandHash,
    ) {}

    public static function success(string $output, string $errorOutput, int $durationMs, string $commandHash): self
    {
        return new self(
            exitCode: 0,
            successful: true,
            output: $output,
            errorOutput: $errorOutput,
            durationMs: $durationMs,
            commandHash: $commandHash,
        );
    }

    public static function failure(int $exitCode, string $output, string $errorOutput, int $durationMs, string $commandHash): self
    {
        return new self(
            exitCode: $exitCode,
            successful: false,
            output: $output,
            errorOutput: $errorOutput,
            durationMs: $durationMs,
            commandHash: $commandHash,
        );
    }

    public function truncatedOutput(int $maxLength = 2000): string
    {
        if (strlen($this->output) <= $maxLength) {
            return $this->output;
        }

        return substr($this->output, 0, $maxLength).'...[truncated]';
    }

    public function truncatedErrorOutput(int $maxLength = 2000): string
    {
        if (strlen($this->errorOutput) <= $maxLength) {
            return $this->errorOutput;
        }

        return substr($this->errorOutput, 0, $maxLength).'...[truncated]';
    }
}
