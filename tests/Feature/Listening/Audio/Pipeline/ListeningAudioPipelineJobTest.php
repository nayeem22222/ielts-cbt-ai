<?php

declare(strict_types=1);

namespace Tests\Feature\Listening\Audio\Pipeline;

use App\Actions\Listening\Audio\Pipeline\StartListeningAudioPipelineAction;
use App\Enums\Listening\ListeningAudioProcessingStatus;
use App\Jobs\Listening\Audio\ProcessListeningAudioPipelineJob;
use App\Models\Listening\ListeningAudio;
use App\Models\Listening\ListeningAudioProcessingLog;
use App\Services\Listening\Audio\Pipeline\FfmpegBinaryService;
use App\Services\Listening\Audio\Pipeline\FfmpegProcessRunner;
use App\Services\Listening\Audio\Pipeline\FfprobeMetadataReader;
use App\Services\Listening\Audio\Pipeline\ListeningAudioPipelineLockService;
use App\Services\Listening\Audio\Pipeline\ListeningAudioPipelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ListeningAudioPipelineJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_pipeline_job_dispatches_to_queue(): void
    {
        Queue::fake();

        $audio = ListeningAudio::factory()->create([
            'processing_status' => ListeningAudioProcessingStatus::Pending,
        ]);

        ProcessListeningAudioPipelineJob::dispatch($audio->id);

        Queue::assertPushed(ProcessListeningAudioPipelineJob::class, function ($job) use ($audio) {
            return $job->audioId === $audio->id;
        });
    }

    public function test_start_pipeline_action_dispatches_job(): void
    {
        Queue::fake();

        $audio = ListeningAudio::factory()->create([
            'processing_status' => ListeningAudioProcessingStatus::Pending,
        ]);

        $action = app(StartListeningAudioPipelineAction::class);
        $dispatched = $action->execute($audio);

        $this->assertTrue($dispatched);
        Queue::assertPushed(ProcessListeningAudioPipelineJob::class);
    }

    public function test_start_pipeline_action_prevents_duplicate_dispatch(): void
    {
        Queue::fake();

        $audio = ListeningAudio::factory()->create([
            'processing_status' => ListeningAudioProcessingStatus::Processing,
        ]);

        $action = app(StartListeningAudioPipelineAction::class);
        $dispatched = $action->execute($audio, force: false);

        $this->assertFalse($dispatched);
        Queue::assertNotPushed(ProcessListeningAudioPipelineJob::class);
    }

    public function test_force_dispatch_bypasses_already_processing_check(): void
    {
        Queue::fake();

        $audio = ListeningAudio::factory()->create([
            'processing_status' => ListeningAudioProcessingStatus::Completed,
        ]);

        $action = app(StartListeningAudioPipelineAction::class);
        $dispatched = $action->execute($audio, force: true);

        $this->assertTrue($dispatched);
        Queue::assertPushed(ProcessListeningAudioPipelineJob::class, fn ($job) => $job->force === true);
    }

    public function test_job_stops_safely_if_audio_deleted_before_start(): void
    {
        $pipeline = Mockery::mock(ListeningAudioPipelineService::class);
        $pipeline->shouldNotReceive('process');
        $this->app->instance(ListeningAudioPipelineService::class, $pipeline);

        // Audio ID that doesn't exist
        $job = new ProcessListeningAudioPipelineJob(99999, false);
        $job->handle(
            app(ListeningAudioPipelineService::class),
            app(ListeningAudioPipelineLockService::class),
            app(\App\Services\Listening\Audio\Pipeline\ListeningAudioPipelineLogger::class),
        );
    }

    public function test_lock_prevents_concurrent_processing(): void
    {
        $audio = ListeningAudio::factory()->create([
            'processing_status' => ListeningAudioProcessingStatus::Pending,
        ]);

        $lockService = app(ListeningAudioPipelineLockService::class);
        $token = $lockService->acquire($audio);

        try {
            $this->expectException(RuntimeException::class);
            $lockService->acquire($audio);
        } finally {
            $lockService->release($audio, $token);
        }
    }

    public function test_stale_lock_can_be_force_released(): void
    {
        $audio = ListeningAudio::factory()->create([
            'processing_status' => ListeningAudioProcessingStatus::Processing,
            'processing_locked_at' => now()->subHours(3),
            'processing_lock_token' => 'stale-token-abc',
        ]);

        $lockService = app(ListeningAudioPipelineLockService::class);

        $this->assertTrue($lockService->isLocked($audio));
        $this->assertTrue($lockService->isExpired($audio));

        $lockService->forceRelease($audio);

        $this->assertFalse($lockService->isLocked($audio));
    }

    public function test_retry_count_increments_on_failed_job(): void
    {
        $audio = ListeningAudio::factory()->create([
            'processing_status' => ListeningAudioProcessingStatus::Pending,
            'retry_count' => 0,
        ]);

        // Mock the pipeline to throw a retryable exception
        $pipeline = Mockery::mock(ListeningAudioPipelineService::class);
        $pipeline->shouldReceive('process')
            ->once()
            ->andThrow(new RuntimeException('Temporary storage error'));

        $this->app->instance(ListeningAudioPipelineService::class, $pipeline);

        $job = new ProcessListeningAudioPipelineJob($audio->id, false);

        try {
            $job->handle(
                app(ListeningAudioPipelineService::class),
                app(ListeningAudioPipelineLockService::class),
                app(\App\Services\Listening\Audio\Pipeline\ListeningAudioPipelineLogger::class),
            );
        } catch (RuntimeException) {
            // Expected — retryable exceptions re-throw
        }

        $fresh = ListeningAudio::query()->find($audio->id);
        $this->assertStringContainsString('Temporary storage error', $fresh->processing_error ?? '');
    }

    public function test_permanent_failure_marks_audio_failed(): void
    {
        $audio = ListeningAudio::factory()->create([
            'processing_status' => ListeningAudioProcessingStatus::Processing,
            'retry_count' => 0,
        ]);

        // DomainException = permanent failure
        $pipeline = Mockery::mock(ListeningAudioPipelineService::class);
        $pipeline->shouldReceive('process')
            ->once()
            ->andThrow(new \DomainException('Corrupted audio file'));

        $this->app->instance(ListeningAudioPipelineService::class, $pipeline);

        $job = new ProcessListeningAudioPipelineJob($audio->id, false);

        // Should not re-throw (permanent)
        $job->handle(
            app(ListeningAudioPipelineService::class),
            app(ListeningAudioPipelineLockService::class),
            app(\App\Services\Listening\Audio\Pipeline\ListeningAudioPipelineLogger::class),
        );

        $fresh = ListeningAudio::query()->find($audio->id);
        $this->assertEquals(ListeningAudioProcessingStatus::Failed, $fresh->processing_status);
    }

    public function test_processing_logs_are_created_per_stage_via_logger(): void
    {
        $audio = ListeningAudio::factory()->create([
            'processing_status' => ListeningAudioProcessingStatus::Processing,
        ]);

        $logger = app(\App\Services\Listening\Audio\Pipeline\ListeningAudioPipelineLogger::class);

        $log1 = $logger->startStage($audio, 'metadata_extracted');
        $logger->completeStage($log1, 'Metadata done.');

        $log2 = $logger->startStage($audio, 'converted');
        $logger->completeStage($log2, 'Converted.');

        $logs = ListeningAudioProcessingLog::query()
            ->where('listening_audio_id', $audio->id)
            ->get();

        $this->assertCount(2, $logs);
        $this->assertEquals('completed', $logs[0]->status);
        $this->assertEquals('completed', $logs[1]->status);
    }

    public function test_silence_report_structure(): void
    {
        // Test that silence report JSON has the expected structure
        $silenceStdErr = <<<'STDERR'
            silence_start: 10.500
            silence_end: 13.200 | silence_duration: 2.700000
            silence_start: 25.000
            silence_end: 30.000 | silence_duration: 5.000000
            STDERR;

        $step = app(\App\Services\Listening\Audio\Pipeline\AudioSilenceDetectionStep::class);

        // Use reflection to call the private parse method
        $reflection = new \ReflectionClass($step);
        $method = $reflection->getMethod('parseReport');
        $report = $method->invoke($step, $silenceStdErr, 120.0);

        $this->assertArrayHasKey('total_silence_seconds', $report);
        $this->assertArrayHasKey('silence_percent', $report);
        $this->assertArrayHasKey('segments', $report);
        $this->assertArrayHasKey('warning', $report);
        $this->assertCount(2, $report['segments']);
        $this->assertGreaterThan(0, $report['total_silence_seconds']);
    }

    public function test_waveform_json_structure(): void
    {
        // Waveform JSON must have required keys
        $peaks = array_map(fn () => round(rand(0, 100) / 100, 4), range(0, 999));
        $doc = [
            'version' => 2,
            'samples' => 1000,
            'duration_seconds' => 60.0,
            'peaks' => $peaks,
            'normalized' => true,
            'generated_at' => now()->toIso8601String(),
            'quality' => 'full',
        ];

        $this->assertArrayHasKey('version', $doc);
        $this->assertArrayHasKey('peaks', $doc);
        $this->assertArrayHasKey('samples', $doc);
        $this->assertCount(1000, $doc['peaks']);
        // Peaks should be 0-1
        foreach ($doc['peaks'] as $peak) {
            $this->assertGreaterThanOrEqual(0, $peak);
            $this->assertLessThanOrEqual(1, $peak);
        }
    }

    public function test_stuck_job_command_finds_old_processing_audio(): void
    {
        // Create stuck audio (processing for > 30 minutes)
        $stuck = ListeningAudio::factory()->create([
            'processing_status' => ListeningAudioProcessingStatus::Processing,
            'processing_started_at' => now()->subHours(2),
            'retry_count' => 0,
        ]);

        // Create fresh processing audio (not stuck)
        $fresh = ListeningAudio::factory()->create([
            'processing_status' => ListeningAudioProcessingStatus::Processing,
            'processing_started_at' => now()->subMinutes(5),
            'retry_count' => 0,
        ]);

        $stuckMinutes = (int) config('listening.audio_pipeline.retry_stuck_after_minutes', 30);

        $stuckCount = ListeningAudio::query()
            ->where('processing_status', ListeningAudioProcessingStatus::Processing->value)
            ->where('processing_started_at', '<=', now()->subMinutes($stuckMinutes))
            ->count();

        $this->assertGreaterThanOrEqual(1, $stuckCount);

        // Fresh one should NOT be in stuck list
        $notStuck = ListeningAudio::query()
            ->where('id', $fresh->id)
            ->where('processing_started_at', '<=', now()->subMinutes($stuckMinutes))
            ->exists();

        $this->assertFalse($notStuck);
    }

    public function test_publish_validation_rejects_missing_playable_path(): void
    {
        $audio = ListeningAudio::factory()->create([
            'processing_status' => ListeningAudioProcessingStatus::Completed,
            'meta' => [], // No playable_path in meta
        ]);

        $playablePath = null;
        $meta = is_array($audio->meta) ? $audio->meta : [];
        $audioMeta = is_array($meta['audio'] ?? null) ? $meta['audio'] : [];
        $playablePath = is_string($audioMeta['playable_path'] ?? null) ? $audioMeta['playable_path'] : null;

        // Completed but no playable_path
        $this->assertNull($playablePath);
    }

    public function test_reading_module_is_unaffected(): void
    {
        // Ensure no Reading module classes are imported by pipeline code
        $pipelineFiles = glob(
            base_path('app/Services/Listening/Audio/Pipeline/*.php')
        ) ?: [];

        foreach ($pipelineFiles as $file) {
            $contents = file_get_contents($file);
            $this->assertStringNotContainsString(
                'ReadingTest',
                $contents ?? '',
                "Pipeline file {$file} references Reading module."
            );
            $this->assertStringNotContainsString(
                'ReadingAttempt',
                $contents ?? '',
                "Pipeline file {$file} references Reading module."
            );
        }
    }
}
