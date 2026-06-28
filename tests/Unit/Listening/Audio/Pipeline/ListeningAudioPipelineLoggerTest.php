<?php

declare(strict_types=1);

namespace Tests\Unit\Listening\Audio\Pipeline;

use App\Enums\Listening\ListeningAudioProcessingStatus;
use App\Models\Listening\ListeningAudio;
use App\Models\Listening\ListeningAudioProcessingLog;
use App\Services\Listening\Audio\Pipeline\ListeningAudioPipelineLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListeningAudioPipelineLoggerTest extends TestCase
{
    use RefreshDatabase;

    private ListeningAudioPipelineLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new ListeningAudioPipelineLogger;
    }

    private function makeAudio(): ListeningAudio
    {
        return ListeningAudio::factory()->create([
            'processing_status' => ListeningAudioProcessingStatus::Processing,
        ]);
    }

    public function test_startStage_creates_log_record(): void
    {
        $audio = $this->makeAudio();
        $log = $this->logger->startStage($audio, 'metadata_extracted');

        $this->assertInstanceOf(ListeningAudioProcessingLog::class, $log);
        $this->assertDatabaseHas('listening_audio_processing_logs', [
            'listening_audio_id' => $audio->id,
            'stage' => 'metadata_extracted',
            'status' => 'started',
        ]);
    }

    public function test_completeStage_updates_log_to_completed(): void
    {
        $audio = $this->makeAudio();
        $log = $this->logger->startStage($audio, 'converted');
        $this->logger->completeStage($log, 'Conversion complete.');

        $this->assertDatabaseHas('listening_audio_processing_logs', [
            'id' => $log->id,
            'status' => 'completed',
            'message' => 'Conversion complete.',
        ]);
    }

    public function test_failStage_marks_log_as_failed(): void
    {
        $audio = $this->makeAudio();
        $log = $this->logger->startStage($audio, 'converted');
        $this->logger->failStage($log, new \RuntimeException('FFmpeg not found'));

        $this->assertDatabaseHas('listening_audio_processing_logs', [
            'id' => $log->id,
            'status' => 'failed',
            'message' => 'FFmpeg not found',
        ]);
    }

    public function test_warning_creates_warning_log(): void
    {
        $audio = $this->makeAudio();
        $this->logger->warning($audio, 'silence_detected', 'High silence detected.');

        $this->assertDatabaseHas('listening_audio_processing_logs', [
            'listening_audio_id' => $audio->id,
            'stage' => 'silence_detected',
            'status' => 'warning',
            'message' => 'High silence detected.',
        ]);
    }

    public function test_skipped_creates_skipped_log(): void
    {
        $audio = $this->makeAudio();
        $this->logger->skipped($audio, 'normalized', 'Normalization disabled.');

        $this->assertDatabaseHas('listening_audio_processing_logs', [
            'listening_audio_id' => $audio->id,
            'stage' => 'normalized',
            'status' => 'skipped',
        ]);
    }

    public function test_updateMetaHistory_stores_in_pipeline_meta(): void
    {
        $audio = $this->makeAudio();
        $this->logger->updateMetaHistory($audio, 'metadata_extracted', 'completed', 'Done', 500);

        $fresh = ListeningAudio::query()->find($audio->id);
        $history = $fresh->pipelineHistory();

        $this->assertNotEmpty($history);
        $lastEntry = end($history);
        $this->assertSame('metadata_extracted', $lastEntry['stage']);
        $this->assertSame('completed', $lastEntry['status']);
        $this->assertSame(500, $lastEntry['duration_ms']);
    }

    public function test_history_is_capped_at_100_entries(): void
    {
        $audio = $this->makeAudio();

        // Insert 110 entries manually
        for ($i = 0; $i < 110; $i++) {
            $this->logger->updateMetaHistory($audio, 'queued', 'completed', "Entry {$i}");
        }

        $fresh = ListeningAudio::query()->find($audio->id);
        $history = $fresh->pipelineHistory();

        $this->assertLessThanOrEqual(100, count($history));
    }

    public function test_log_has_duration_ms_after_complete(): void
    {
        $audio = $this->makeAudio();
        $log = $this->logger->startStage($audio, 'waveform_generated');

        // Simulate 100ms delay
        usleep(100_000);

        $this->logger->completeStage($log, 'Waveform done.');

        $freshLog = ListeningAudioProcessingLog::query()->find($log->id);
        $this->assertGreaterThan(0, $freshLog->duration_ms);
    }
}
