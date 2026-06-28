<?php

declare(strict_types=1);

namespace App\Services\Listening\Audio\Pipeline;

use App\Models\Listening\ListeningAudio;
use App\Models\Listening\ListeningAudioProcessingLog;
use Illuminate\Support\Facades\DB;

class ListeningAudioPipelineLogger
{
    /**
     * Start a new stage log entry.
     *
     * @param  array<string, mixed>  $context
     */
    public function startStage(
        ListeningAudio $audio,
        string $stage,
        array $context = [],
        ?string $jobId = null,
    ): ListeningAudioProcessingLog {
        /** @var ListeningAudioProcessingLog $log */
        $log = ListeningAudioProcessingLog::query()->create([
            'listening_audio_id' => $audio->id,
            'job_id' => $jobId,
            'stage' => $stage,
            'status' => 'started',
            'context' => $this->safeContext($context),
            'started_at' => now(),
        ]);

        $this->updateMetaStage($audio, $stage);

        return $log;
    }

    /**
     * Mark a stage log as completed.
     *
     * @param  array<string, mixed>  $context
     */
    public function completeStage(
        ListeningAudioProcessingLog $log,
        ?string $message = null,
        array $context = [],
    ): void {
        $finished = now();
        $durationMs = $log->started_at
            ? (int) ($log->started_at->diffInMilliseconds($finished))
            : 0;

        $log->update([
            'status' => 'completed',
            'message' => $message ?? 'Stage completed.',
            'context' => $this->safeContext(array_merge($log->context ?? [], $context)),
            'duration_ms' => $durationMs,
            'finished_at' => $finished,
        ]);

        $audio = ListeningAudio::query()->find($log->listening_audio_id);

        if ($audio !== null) {
            $this->updateMetaHistory($audio, $log->stage, 'completed', $message ?? 'Stage completed.', $durationMs);
        }
    }

    /**
     * Mark a stage log as failed.
     *
     * @param  array<string, mixed>  $context
     */
    public function failStage(
        ListeningAudioProcessingLog $log,
        \Throwable|string $error,
        array $context = [],
    ): void {
        $message = $error instanceof \Throwable ? $error->getMessage() : $error;
        $finished = now();
        $durationMs = $log->started_at
            ? (int) ($log->started_at->diffInMilliseconds($finished))
            : 0;

        $log->update([
            'status' => 'failed',
            'message' => $message,
            'context' => $this->safeContext(array_merge($log->context ?? [], $context, [
                'error_class' => $error instanceof \Throwable ? get_class($error) : null,
            ])),
            'duration_ms' => $durationMs,
            'finished_at' => $finished,
        ]);

        $audio = ListeningAudio::query()->find($log->listening_audio_id);

        if ($audio !== null) {
            $this->updateMetaHistory($audio, $log->stage, 'failed', $message, $durationMs);
        }
    }

    /**
     * Log a warning entry.
     *
     * @param  array<string, mixed>  $context
     */
    public function warning(
        ListeningAudio $audio,
        string $stage,
        string $message,
        array $context = [],
        ?string $jobId = null,
    ): void {
        ListeningAudioProcessingLog::query()->create([
            'listening_audio_id' => $audio->id,
            'job_id' => $jobId,
            'stage' => $stage,
            'status' => 'warning',
            'message' => $message,
            'context' => $this->safeContext($context),
            'started_at' => now(),
            'finished_at' => now(),
            'duration_ms' => 0,
        ]);

        $this->updateMetaHistory($audio, $stage, 'warning', $message);
    }

    /**
     * Log a skipped entry.
     *
     * @param  array<string, mixed>  $context
     */
    public function skipped(
        ListeningAudio $audio,
        string $stage,
        string $message,
        array $context = [],
        ?string $jobId = null,
    ): void {
        ListeningAudioProcessingLog::query()->create([
            'listening_audio_id' => $audio->id,
            'job_id' => $jobId,
            'stage' => $stage,
            'status' => 'skipped',
            'message' => $message,
            'context' => $this->safeContext($context),
            'started_at' => now(),
            'finished_at' => now(),
            'duration_ms' => 0,
        ]);

        $this->updateMetaHistory($audio, $stage, 'skipped', $message);
    }

    /**
     * Update current stage in meta and add history entry.
     *
     * @param  array<string, mixed>  $extra
     */
    public function updateMetaHistory(
        ListeningAudio $audio,
        string $stage,
        string $status,
        string $message = '',
        int $durationMs = 0,
        array $extra = [],
    ): void {
        $meta = is_array($audio->meta) ? $audio->meta : [];
        $pipeline = is_array($meta['pipeline'] ?? null) ? $meta['pipeline'] : [];

        $entry = array_merge([
            'stage' => $stage,
            'status' => $status,
            'started_at' => now()->toIso8601String(),
            'finished_at' => now()->toIso8601String(),
            'duration_ms' => $durationMs,
            'message' => $message,
        ], $extra);

        $history = is_array($pipeline['history'] ?? null) ? $pipeline['history'] : [];
        $history[] = $entry;

        // Cap to last 100
        if (count($history) > 100) {
            $history = array_slice($history, -100);
        }

        $pipeline['history'] = $history;
        $pipeline['last_stage'] = $stage;
        $pipeline['last_status'] = $status;

        $meta['pipeline'] = $pipeline;

        DB::table('listening_audios')
            ->where('id', $audio->id)
            ->update(['meta' => json_encode($meta), 'updated_at' => now()]);

        $audio->forceFill(['meta' => $meta]);
    }

    private function updateMetaStage(ListeningAudio $audio, string $stage): void
    {
        $meta = is_array($audio->meta) ? $audio->meta : [];
        $pipeline = is_array($meta['pipeline'] ?? null) ? $meta['pipeline'] : [];
        $pipeline['current_stage'] = $stage;
        $meta['pipeline'] = $pipeline;

        DB::table('listening_audios')
            ->where('id', $audio->id)
            ->update(['meta' => json_encode($meta), 'updated_at' => now()]);

        $audio->forceFill(['meta' => $meta]);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function safeContext(array $context): array
    {
        // Recursively truncate long strings to avoid bloating the JSON
        array_walk_recursive($context, function (mixed &$value): void {
            if (is_string($value) && strlen($value) > 1000) {
                $value = substr($value, 0, 1000).'...[truncated]';
            }
        });

        return $context;
    }
}
