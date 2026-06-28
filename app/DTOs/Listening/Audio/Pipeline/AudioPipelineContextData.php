<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Audio\Pipeline;

final class AudioPipelineContextData
{
    public ?string $lockToken = null;

    public ?string $jobId = null;

    public ?string $versionTag = null;

    public ?string $processedPath = null;

    public ?string $normalizedPath = null;

    public ?string $waveformJsonPath = null;

    public ?string $waveformPreviewPath = null;

    /** @var list<float> */
    public array $peaks = [];

    public ?float $durationSeconds = null;

    public ?int $bitrate = null;

    public ?int $sampleRate = null;

    public ?int $channels = null;

    public ?string $format = null;

    public ?float $loudnessLufs = null;

    public ?float $peakDb = null;

    /** @var array<string, mixed>|null */
    public ?array $silenceReport = null;

    /** @var list<string> */
    public array $warnings = [];

    /** @var list<array{stage: string, status: string, started_at: string, finished_at: string, duration_ms: int, message: string}> */
    public array $history = [];

    public function __construct(
        public readonly int $audioId,
        public readonly bool $force,
        public readonly string $pipelineVersion,
    ) {
        $this->versionTag = 'v'.time();
    }

    public function addWarning(string $message): void
    {
        $this->warnings[] = $message;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public function addHistory(string $stage, string $status, string $message, int $durationMs = 0, array $extra = []): void
    {
        $entry = array_merge([
            'stage' => $stage,
            'status' => $status,
            'started_at' => now()->toIso8601String(),
            'finished_at' => now()->toIso8601String(),
            'duration_ms' => $durationMs,
            'message' => $message,
        ], $extra);

        $this->history[] = $entry;

        if (count($this->history) > 100) {
            $this->history = array_slice($this->history, -100);
        }
    }

    public function playablePath(): ?string
    {
        return $this->normalizedPath ?? $this->processedPath;
    }
}
