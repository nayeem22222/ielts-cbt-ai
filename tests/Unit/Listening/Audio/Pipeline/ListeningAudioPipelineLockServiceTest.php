<?php

declare(strict_types=1);

namespace Tests\Unit\Listening\Audio\Pipeline;

use App\Enums\Listening\ListeningAudioProcessingStatus;
use App\Models\Listening\ListeningAudio;
use App\Services\Listening\Audio\Pipeline\ListeningAudioPipelineLockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ListeningAudioPipelineLockServiceTest extends TestCase
{
    use RefreshDatabase;

    private ListeningAudioPipelineLockService $lockService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lockService = new ListeningAudioPipelineLockService;
    }

    private function makeAudio(): ListeningAudio
    {
        return ListeningAudio::factory()->create([
            'processing_status' => ListeningAudioProcessingStatus::Pending,
            'processing_locked_at' => null,
            'processing_lock_token' => null,
        ]);
    }

    public function test_acquire_returns_token(): void
    {
        $audio = $this->makeAudio();
        $token = $this->lockService->acquire($audio);

        $this->assertNotEmpty($token);
        $this->assertIsString($token);
    }

    public function test_isLocked_returns_true_after_acquire(): void
    {
        $audio = $this->makeAudio();
        $this->lockService->acquire($audio);

        $this->assertTrue($this->lockService->isLocked($audio));
    }

    public function test_release_with_matching_token_clears_lock(): void
    {
        $audio = $this->makeAudio();
        $token = $this->lockService->acquire($audio);

        $this->lockService->release($audio, $token);

        $this->assertFalse($this->lockService->isLocked($audio));
    }

    public function test_release_with_wrong_token_does_not_clear_lock(): void
    {
        $audio = $this->makeAudio();
        $this->lockService->acquire($audio);

        $this->lockService->release($audio, 'wrong-token');

        $this->assertTrue($this->lockService->isLocked($audio));
    }

    public function test_force_release_clears_lock_regardless_of_token(): void
    {
        $audio = $this->makeAudio();
        $this->lockService->acquire($audio);

        $this->lockService->forceRelease($audio);

        $this->assertFalse($this->lockService->isLocked($audio));
    }

    public function test_isExpired_returns_true_for_stale_lock(): void
    {
        $audio = $this->makeAudio();

        // Manually create a stale lock
        \Illuminate\Support\Facades\DB::table('listening_audios')
            ->where('id', $audio->id)
            ->update([
                'processing_locked_at' => now()->subHours(2),
                'processing_lock_token' => 'old-token',
            ]);

        $this->assertTrue($this->lockService->isExpired($audio));
    }

    public function test_isExpired_returns_false_for_fresh_lock(): void
    {
        $audio = $this->makeAudio();
        $this->lockService->acquire($audio);

        $this->assertFalse($this->lockService->isExpired($audio));
    }

    public function test_acquire_fails_if_lock_active_and_not_expired(): void
    {
        $audio = $this->makeAudio();
        $this->lockService->acquire($audio);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/already being processed/i');

        $this->lockService->acquire($audio);
    }
}
