<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Listening\ListeningAudioProcessingStatus;
use App\Enums\Listening\ListeningAudioValidationStatus;
use App\Enums\Listening\ListeningDifficultyLevel;
use App\Enums\Listening\ListeningSectionType;
use App\Enums\Listening\ListeningTestStatus;
use App\Enums\Listening\ListeningTestType;
use App\Jobs\Listening\ProcessListeningAudioJob;
use App\Models\Listening\ListeningAudio;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Models\User;
use App\Services\Listening\Audio\ListeningAudioProcessingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    seedRbac();
});

function createListeningAudioAdmin(): User
{
    return createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'listening-audio-admin-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);
}

function createListeningTestForAudio(User $admin, array $overrides = []): ListeningTest
{
    return ListeningTest::query()->create(array_merge([
        'title' => 'Audio Test '.uniqid(),
        'slug' => 'audio-test-'.uniqid(),
        'test_code' => 'LST-A-'.strtoupper(uniqid()),
        'test_type' => ListeningTestType::Academic,
        'difficulty_level' => ListeningDifficultyLevel::Official,
        'duration_minutes' => 30,
        'status' => ListeningTestStatus::Draft,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ], $overrides));
}

function createListeningSectionForAudio(ListeningTest $test, ?int $audioId = null, int $sectionNumber = 1): ListeningSection
{
    $ranges = [1 => [1, 10], 2 => [11, 20], 3 => [21, 30], 4 => [31, 40]];
    [$start, $end] = $ranges[$sectionNumber];

    return ListeningSection::query()->create([
        'listening_test_id' => $test->id,
        'section_number' => $sectionNumber,
        'title' => 'Section '.$sectionNumber,
        'section_type' => ListeningSectionType::Conversation,
        'audio_id' => $audioId,
        'total_questions' => 10,
        'start_question_number' => $start,
        'end_question_number' => $end,
        'display_order' => $sectionNumber,
        'is_active' => true,
    ]);
}

function fakeMp3Upload(string $name = 'section-1.mp3'): UploadedFile
{
    return UploadedFile::fake()->create($name, 512, 'audio/mpeg');
}

it('allows admin to view audio list', function (): void {
    $admin = createListeningAudioAdmin();

    $this->actingAs($admin)
        ->get(route('admin.listening.audios.index'))
        ->assertOk()
        ->assertSee('Listening Audio Library');
});

it('rejects invalid audio file type on upload', function (): void {
    $admin = createListeningAudioAdmin();
    Storage::fake('public');

    $this->actingAs($admin)
        ->post(route('admin.listening.audios.store'), [
            'audio_file' => UploadedFile::fake()->create('notes.txt', 100, 'text/plain'),
            'title' => 'Bad Upload',
        ])
        ->assertSessionHasErrors('audio_file');
});

it('creates audio record and dispatches processing job on valid upload', function (): void {
    $admin = createListeningAudioAdmin();
    Storage::fake('public');
    Queue::fake();

    $response = $this->actingAs($admin)
        ->post(route('admin.listening.audios.store'), [
            'audio_file' => fakeMp3Upload(),
            'title' => 'Section 1 Audio',
            'description' => 'Test upload',
        ]);

    $audio = ListeningAudio::query()->first();
    expect($audio)->not->toBeNull()
        ->and($audio->original_name)->toBe('section-1.mp3')
        ->and($audio->processing_status)->toBe(ListeningAudioProcessingStatus::Pending);

    Queue::assertPushed(ProcessListeningAudioJob::class, fn (ProcessListeningAudioJob $job): bool => $job->audioId === $audio->id);

    $response->assertRedirect(route('admin.listening.audios.show', $audio))
        ->assertSessionHas('status');
});

it('marks processing completed and saves metadata after job runs', function (): void {
    Storage::fake('public');
    $admin = createListeningAudioAdmin();

    $this->actingAs($admin)
        ->post(route('admin.listening.audios.store'), [
            'audio_file' => fakeMp3Upload(),
            'title' => 'Processed Audio',
        ]);

    $audio = ListeningAudio::query()->firstOrFail();
    app(ListeningAudioProcessingService::class)->process($audio->fresh());
    $audio->refresh();

    expect($audio->processing_status)->toBe(ListeningAudioProcessingStatus::Completed)
        ->and($audio->validation_status)->toBe(ListeningAudioValidationStatus::Valid)
        ->and($audio->duration_seconds)->toBeGreaterThan(0)
        ->and($audio->waveform_json_path)->not->toBeNull();
});

it('saves processing error when ffmpeg is unavailable', function (): void {
    app()->instance(
        \App\Services\Listening\Audio\ListeningFfmpegRunnerInterface::class,
        new class implements \App\Services\Listening\Audio\ListeningFfmpegRunnerInterface
        {
            public function isFfmpegAvailable(): bool
            {
                return false;
            }

            public function isFfprobeAvailable(): bool
            {
                return false;
            }

            public function probe(string $absolutePath): array
            {
                return [];
            }

            public function convert(string $inputPath, string $outputPath): void {}

            public function normalize(string $inputPath, string $outputPath, float $targetLufs): void {}

            public function extractPeaks(string $absolutePath, int $samples): array
            {
                return [];
            }
        },
    );

    Storage::fake('public');
    $admin = createListeningAudioAdmin();

    $this->actingAs($admin)
        ->post(route('admin.listening.audios.store'), [
            'audio_file' => fakeMp3Upload('broken.mp3'),
        ]);

    $audio = ListeningAudio::query()->firstOrFail();
    app(ListeningAudioProcessingService::class)->process($audio->fresh());
    $audio->refresh();

    expect($audio->processing_status)->toBe(ListeningAudioProcessingStatus::Failed)
        ->and($audio->processing_error)->toContain('FFmpeg is not available');
});

it('increments retry count and enforces retry limit', function (): void {
    Storage::fake('public');
    $admin = createListeningAudioAdmin();
    $audio = ListeningAudio::query()->create([
        'original_name' => 'retry.mp3',
        'stored_name' => 'retry.mp3',
        'disk' => 'public',
        'path' => 'listening/audio/original/retry.mp3',
        'processing_status' => ListeningAudioProcessingStatus::Failed,
        'validation_status' => ListeningAudioValidationStatus::Invalid,
        'retry_count' => 3,
        'uploaded_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.listening.audios.retry', $audio))
        ->assertSessionHasErrors();

    $this->actingAs($admin)
        ->post(route('admin.listening.audios.retry', $audio), ['force' => true])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($audio->fresh()->retry_count)->toBe(3);
});

it('generates waveform json with valid structure', function (): void {
    Storage::fake('public');
    $admin = createListeningAudioAdmin();

    $this->actingAs($admin)
        ->post(route('admin.listening.audios.store'), ['audio_file' => fakeMp3Upload('wave.mp3')]);

    $audio = ListeningAudio::query()->firstOrFail();
    app(ListeningAudioProcessingService::class)->process($audio->fresh());
    $audio->refresh();

    $json = Storage::disk('public')->get((string) $audio->waveform_json_path);
    $decoded = json_decode($json, true);

    expect($decoded)->toBeArray()
        ->and($decoded['version'])->toBe(1)
        ->and($decoded['peaks'])->toBeArray()
        ->and($decoded['samples'])->toBe(count($decoded['peaks']));
});

it('prevents deleting audio attached to a section', function (): void {
    Storage::fake('public');
    $admin = createListeningAudioAdmin();
    $audio = ListeningAudio::query()->create([
        'original_name' => 'attached.mp3',
        'stored_name' => 'attached.mp3',
        'disk' => 'public',
        'path' => 'listening/audio/original/attached.mp3',
        'processing_status' => ListeningAudioProcessingStatus::Completed,
        'validation_status' => ListeningAudioValidationStatus::Valid,
        'duration_seconds' => 120,
        'uploaded_by' => $admin->id,
    ]);

    $test = createListeningTestForAudio($admin);
    createListeningSectionForAudio($test, $audio->id);

    $this->actingAs($admin)
        ->delete(route('admin.listening.audios.destroy', $audio))
        ->assertSessionHasErrors();
});

it('denies unauthorized user from managing audio', function (): void {
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'audio-student-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($student)
        ->get(route('admin.listening.audios.index'))
        ->assertForbidden();
});

it('publish rejects unprocessed section audio', function (): void {
    $admin = createListeningAudioAdmin();
    $audio = ListeningAudio::query()->create([
        'original_name' => 'pending.mp3',
        'stored_name' => 'pending.mp3',
        'disk' => 'public',
        'path' => 'listening/audio/original/pending.mp3',
        'processing_status' => ListeningAudioProcessingStatus::Pending,
        'validation_status' => ListeningAudioValidationStatus::Pending,
        'uploaded_by' => $admin->id,
    ]);

    $test = createListeningTestForAudio($admin);
    $section = createListeningSectionForAudio($test, $audio->id);

    $result = app(\App\Actions\Listening\PublishListeningTestAction::class)->validate($test->fresh());

    expect($result['errors'])->toContain("Section {$section->section_number} audio is not processed.");
});
