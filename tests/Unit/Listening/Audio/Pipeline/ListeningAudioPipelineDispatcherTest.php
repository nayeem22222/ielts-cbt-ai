<?php

declare(strict_types=1);

namespace Tests\Unit\Listening\Audio\Pipeline;

use App\Jobs\Listening\Audio\ProcessListeningAudioPipelineJob;
use App\Services\Listening\Audio\Pipeline\ListeningAudioPipelineDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ListeningAudioPipelineDispatcherTest extends TestCase
{
    use RefreshDatabase;
    public function test_resolves_configured_connection_when_not_sync(): void
    {
        config(['listening.audio_pipeline.connection' => 'redis']);

        $this->assertSame('redis', ListeningAudioPipelineDispatcher::connection());
        $this->assertFalse(ListeningAudioPipelineDispatcher::usesSyncDriver());
    }

    public function test_falls_back_to_database_when_sync_is_configured(): void
    {
        config(['listening.audio_pipeline.connection' => 'sync']);
        config(['queue.default' => 'sync']);

        $this->assertSame('database', ListeningAudioPipelineDispatcher::connection());
        $this->assertTrue(ListeningAudioPipelineDispatcher::usesSyncDriver());
    }

    public function test_worker_command_uses_pipeline_settings(): void
    {
        config([
            'listening.audio_pipeline.connection' => 'database',
            'listening.audio_pipeline.queue' => 'listening-audio',
            'listening.audio_pipeline.job_timeout_seconds' => 900,
            'listening.audio_pipeline.job_tries' => 3,
        ]);

        $this->assertSame(
            'php artisan queue:work database --queue=listening-audio --timeout=900 --tries=3',
            ListeningAudioPipelineDispatcher::workerCommand(),
        );
    }

    public function test_dispatch_skips_when_job_already_queued(): void
    {
        config(['listening.audio_pipeline.queue' => 'listening-audio']);

        $needle = '"audioId";i:42;';
        DB::table('jobs')->insert([
            'queue' => 'listening-audio',
            'payload' => json_encode([
                'displayName' => ProcessListeningAudioPipelineJob::class,
                'data' => ['commandName' => ProcessListeningAudioPipelineJob::class],
            ]).$needle,
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        $before = ListeningAudioPipelineDispatcher::pendingJobCount();
        ListeningAudioPipelineDispatcher::dispatch(42, force: false);
        $after = ListeningAudioPipelineDispatcher::pendingJobCount();

        $this->assertSame($before, $after);
    }
}
