<?php

declare(strict_types=1);

namespace Database\Factories\Listening;

use App\Enums\Listening\ListeningAudioProcessingStatus;
use App\Enums\Listening\ListeningAudioValidationStatus;
use App\Models\Listening\ListeningAudio;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ListeningAudio>
 */
class ListeningAudioFactory extends Factory
{
    protected $model = ListeningAudio::class;

    public function definition(): array
    {
        $name = $this->faker->words(3, true).'.mp3';

        return [
            'original_name' => $name,
            'stored_name' => Str::uuid().'.mp3',
            'disk' => 'public',
            'path' => 'listening/audio/original/'.Str::uuid().'.mp3',
            'mime_type' => 'audio/mpeg',
            'extension' => 'mp3',
            'file_size' => $this->faker->numberBetween(500_000, 50_000_000),
            'duration_seconds' => $this->faker->numberBetween(60, 1800),
            'processing_status' => ListeningAudioProcessingStatus::Pending,
            'validation_status' => ListeningAudioValidationStatus::Pending,
            'retry_count' => 0,
            'meta' => null,
        ];
    }

    public function completed(): self
    {
        return $this->state([
            'processing_status' => ListeningAudioProcessingStatus::Completed,
            'validation_status' => ListeningAudioValidationStatus::Valid,
            'processing_finished_at' => now(),
            'last_processed_at' => now(),
        ]);
    }

    public function failed(): self
    {
        return $this->state([
            'processing_status' => ListeningAudioProcessingStatus::Failed,
            'validation_status' => ListeningAudioValidationStatus::Invalid,
            'processing_error' => 'Simulated failure.',
        ]);
    }

    public function processing(): self
    {
        return $this->state([
            'processing_status' => ListeningAudioProcessingStatus::Processing,
            'processing_started_at' => now(),
        ]);
    }

    public function locked(): self
    {
        return $this->state([
            'processing_locked_at' => now(),
            'processing_lock_token' => Str::random(40),
        ]);
    }
}
