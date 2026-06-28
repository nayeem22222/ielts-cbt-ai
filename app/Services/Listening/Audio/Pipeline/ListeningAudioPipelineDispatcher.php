<?php

declare(strict_types=1);

namespace App\Services\Listening\Audio\Pipeline;

use App\Jobs\Listening\Audio\ProcessListeningAudioPipelineJob;
use Illuminate\Support\Facades\DB;

class ListeningAudioPipelineDispatcher
{
    /**
     * Resolve the queue connection for listening audio pipeline jobs.
     *
     * The sync driver runs FFmpeg inside the HTTP request and hits max_execution_time.
     * When sync is configured, we always dispatch to the database queue instead.
     */
    public static function connection(): string
    {
        $connection = (string) config(
            'listening.audio_pipeline.connection',
            config('queue.default', 'database'),
        );

        if ($connection === 'sync') {
            return 'database';
        }

        return $connection;
    }

    public static function usesSyncDriver(): bool
    {
        $configured = (string) config(
            'listening.audio_pipeline.connection',
            config('queue.default', 'database'),
        );

        return $configured === 'sync';
    }

    public static function queue(): string
    {
        return (string) config('listening.audio_pipeline.queue', 'listening-audio');
    }

    public static function dispatch(int $audioId, bool $force = false): void
    {
        if (! $force && self::hasQueuedJobForAudio($audioId)) {
            return;
        }

        ProcessListeningAudioPipelineJob::dispatch($audioId, $force)
            ->onQueue(self::queue())
            ->onConnection(self::connection());
    }

    public static function pendingJobCount(): int
    {
        return (int) DB::table('jobs')->where('queue', self::queue())->count();
    }

    public static function hasQueuedJobForAudio(int $audioId): bool
    {
        $needle = '"audioId";i:'.$audioId.';';

        return DB::table('jobs')
            ->where('queue', self::queue())
            ->where('payload', 'like', '%ProcessListeningAudioPipelineJob%')
            ->where('payload', 'like', '%'.$needle.'%')
            ->exists();
    }

    public static function workerCommand(): string
    {
        return sprintf(
            'php artisan queue:work %s --queue=%s --timeout=%d --tries=%d',
            self::connection(),
            self::queue(),
            (int) config('listening.audio_pipeline.job_timeout_seconds', 900),
            (int) config('listening.audio_pipeline.job_tries', 3),
        );
    }

    public static function queuedStatusMessage(): string
    {
        return 'Processing is queued. Start a queue worker in a separate terminal: '.self::workerCommand();
    }
}
