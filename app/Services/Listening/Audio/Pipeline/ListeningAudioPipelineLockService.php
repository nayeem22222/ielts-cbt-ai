<?php

declare(strict_types=1);

namespace App\Services\Listening\Audio\Pipeline;

use App\Models\Listening\ListeningAudio;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ListeningAudioPipelineLockService
{
    private function lockKey(ListeningAudio $audio): string
    {
        return "listening_audio_pipeline_lock:{$audio->id}";
    }

    private function ttl(): int
    {
        return (int) config('listening.audio_pipeline.lock_ttl_seconds', 900);
    }

    /**
     * Acquire an exclusive processing lock.
     *
     * @throws RuntimeException If lock cannot be acquired.
     */
    public function acquire(ListeningAudio $audio): string
    {
        $token = Str::random(40);
        $ttl = $this->ttl();

        // Try cache lock first (works with Redis)
        $cacheLock = Cache::lock($this->lockKey($audio), $ttl);

        if (! $cacheLock->get()) {
            // Check if DB lock exists and is not stale
            if ($this->isLocked($audio) && ! $this->isExpired($audio)) {
                throw new RuntimeException(
                    "Audio #{$audio->id} is already being processed. Another job holds the lock."
                );
            }
        }

        // Store in DB as fallback / source of truth
        DB::table('listening_audios')
            ->where('id', $audio->id)
            ->update([
                'processing_locked_at' => now(),
                'processing_lock_token' => $token,
                'updated_at' => now(),
            ]);

        return $token;
    }

    /**
     * Release lock if token matches.
     */
    public function release(ListeningAudio $audio, string $token): void
    {
        $fresh = ListeningAudio::query()->find($audio->id);

        if ($fresh === null) {
            return;
        }

        if ($fresh->processing_lock_token !== $token) {
            return; // Token mismatch — do not release someone else's lock
        }

        DB::table('listening_audios')
            ->where('id', $audio->id)
            ->update([
                'processing_locked_at' => null,
                'processing_lock_token' => null,
                'updated_at' => now(),
            ]);

        try {
            Cache::lock($this->lockKey($audio), $this->ttl())->forceRelease();
        } catch (\Throwable) {
            // Cache might already be expired
        }
    }

    /**
     * Check if there is an active DB lock.
     */
    public function isLocked(ListeningAudio $audio): bool
    {
        $fresh = ListeningAudio::query()->find($audio->id);

        return $fresh !== null
            && $fresh->processing_locked_at !== null
            && $fresh->processing_lock_token !== null;
    }

    /**
     * Check if the DB lock is stale (older than TTL).
     */
    public function isExpired(ListeningAudio $audio): bool
    {
        $fresh = ListeningAudio::query()->find($audio->id);

        if ($fresh === null || $fresh->processing_locked_at === null) {
            return true;
        }

        return $fresh->processing_locked_at->diffInSeconds(now()) >= $this->ttl();
    }

    /**
     * Forcefully release a stale lock regardless of token.
     */
    public function forceRelease(ListeningAudio $audio): void
    {
        DB::table('listening_audios')
            ->where('id', $audio->id)
            ->update([
                'processing_locked_at' => null,
                'processing_lock_token' => null,
                'updated_at' => now(),
            ]);

        try {
            Cache::lock($this->lockKey($audio), $this->ttl())->forceRelease();
        } catch (\Throwable) {
            // Silent
        }
    }
}
