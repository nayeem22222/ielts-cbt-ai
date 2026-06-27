<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Listening\ListeningDifficultyLevel;
use App\Enums\Listening\ListeningTestStatus;
use App\Enums\Listening\ListeningTestType;
use App\Enums\Listening\ListeningTranscriptVisibility;
use App\Models\Listening\ListeningAudio;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Models\Listening\ListeningTranscript;
use App\Models\User;
use App\Services\Listening\ListeningSectionService;

beforeEach(function (): void {
    seedRbac();
});

function createListeningTranscriptAdmin(): User
{
    return createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'listening-transcripts-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);
}

function createListeningTestForTranscripts(User $admin, array $overrides = []): ListeningTest
{
    return ListeningTest::query()->create(array_merge([
        'title' => 'Transcript Test '.uniqid(),
        'slug' => 'transcript-test-'.uniqid(),
        'test_code' => 'LTR-'.strtoupper(uniqid()),
        'test_type' => ListeningTestType::Academic,
        'difficulty_level' => ListeningDifficultyLevel::Official,
        'duration_minutes' => 30,
        'status' => ListeningTestStatus::Draft,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ], $overrides));
}

function validTranscriptPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Sample Transcript',
        'transcript_text' => 'Good morning. How can I help you today with your booking?',
        'language' => 'en',
        'visibility' => ListeningTranscriptVisibility::AdminOnly->value,
        'is_official' => false,
        'source_type' => 'manual',
    ], $overrides);
}

function createListeningAudioForTranscripts(User $admin, array $overrides = []): ListeningAudio
{
    return ListeningAudio::query()->create(array_merge([
        'original_name' => 'audio-'.uniqid().'.mp3',
        'stored_name' => 'audio-'.uniqid().'.mp3',
        'disk' => 'local',
        'path' => 'listening/audio.mp3',
        'duration_seconds' => 120,
        'processing_status' => 'completed',
        'validation_status' => 'valid',
        'uploaded_by' => $admin->id,
    ], $overrides));
}

it('allows admin to view transcript list', function (): void {
    $admin = createListeningTranscriptAdmin();

    ListeningTranscript::query()->create(array_merge(validTranscriptPayload(), [
        'created_by' => $admin->id,
    ]));

    $this->actingAs($admin)
        ->get(route('admin.listening.transcripts.index'))
        ->assertOk()
        ->assertSee('Listening Transcripts')
        ->assertSee('Sample Transcript');
});

it('allows admin to create transcript', function (): void {
    $admin = createListeningTranscriptAdmin();

    $this->actingAs($admin)
        ->post(route('admin.listening.transcripts.store'), validTranscriptPayload())
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(ListeningTranscript::query()->where('title', 'Sample Transcript')->exists())->toBeTrue();
});

it('allows admin to update transcript', function (): void {
    $admin = createListeningTranscriptAdmin();
    $transcript = ListeningTranscript::query()->create(array_merge(validTranscriptPayload(), [
        'created_by' => $admin->id,
    ]));

    $this->actingAs($admin)
        ->put(route('admin.listening.transcripts.update', $transcript), validTranscriptPayload([
            'title' => 'Updated Transcript Title',
        ]))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($transcript->fresh()?->title)->toBe('Updated Transcript Title');
});

it('allows admin to delete transcript', function (): void {
    $admin = createListeningTranscriptAdmin();
    $transcript = ListeningTranscript::query()->create(array_merge(validTranscriptPayload(), [
        'created_by' => $admin->id,
    ]));

    $this->actingAs($admin)
        ->delete(route('admin.listening.transcripts.destroy', $transcript))
        ->assertRedirect(route('admin.listening.transcripts.index'))
        ->assertSessionHas('status');

    expect(ListeningTranscript::withTrashed()->find($transcript->id)?->trashed())->toBeTrue();
});

it('requires transcript_text when creating transcript', function (): void {
    $admin = createListeningTranscriptAdmin();

    $this->actingAs($admin)
        ->from(route('admin.listening.transcripts.create'))
        ->post(route('admin.listening.transcripts.store'), validTranscriptPayload([
            'transcript_text' => 'short',
        ]))
        ->assertRedirect(route('admin.listening.transcripts.create'))
        ->assertSessionHasErrors('transcript_text');
});

it('allows admin to attach transcript to section', function (): void {
    $admin = createListeningTranscriptAdmin();
    $test = createListeningTestForTranscripts($admin);
    $audio = createListeningAudioForTranscripts($admin);
    $transcript = ListeningTranscript::query()->create(array_merge(validTranscriptPayload(), [
        'listening_audio_id' => $audio->id,
        'created_by' => $admin->id,
    ]));

    $section = ListeningSection::query()->create([
        'listening_test_id' => $test->id,
        'section_number' => 1,
        'title' => 'Section 1',
        'section_type' => 'conversation',
        'audio_id' => $audio->id,
        'start_question_number' => 1,
        'end_question_number' => 10,
        'total_questions' => 10,
        'display_order' => 1,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.listening.tests.sections.transcript.attach', [$test, $section]), [
            'transcript_id' => $transcript->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($section->fresh()?->transcript_id)->toBe($transcript->id);
});

it('allows admin to detach transcript from section', function (): void {
    $admin = createListeningTranscriptAdmin();
    $test = createListeningTestForTranscripts($admin);
    $transcript = ListeningTranscript::query()->create(array_merge(validTranscriptPayload(), [
        'created_by' => $admin->id,
    ]));

    $section = ListeningSection::query()->create([
        'listening_test_id' => $test->id,
        'section_number' => 1,
        'title' => 'Section 1',
        'section_type' => 'conversation',
        'transcript_id' => $transcript->id,
        'start_question_number' => 1,
        'end_question_number' => 10,
        'total_questions' => 10,
        'display_order' => 1,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.listening.tests.sections.transcript.detach', [$test, $section]))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($section->fresh()?->transcript_id)->toBeNull();
});

it('rejects transcript audio mismatch in strict mode', function (): void {
    $admin = createListeningTranscriptAdmin();
    $test = createListeningTestForTranscripts($admin);
    $sectionAudio = createListeningAudioForTranscripts($admin);
    $otherAudio = createListeningAudioForTranscripts($admin);
    $transcript = ListeningTranscript::query()->create(array_merge(validTranscriptPayload(), [
        'listening_audio_id' => $otherAudio->id,
        'created_by' => $admin->id,
    ]));

    $section = ListeningSection::query()->create([
        'listening_test_id' => $test->id,
        'section_number' => 1,
        'title' => 'Section 1',
        'section_type' => 'conversation',
        'audio_id' => $sectionAudio->id,
        'start_question_number' => 1,
        'end_question_number' => 10,
        'total_questions' => 10,
        'display_order' => 1,
        'is_active' => true,
    ]);

    config(['listening.transcript.strict_audio_match' => true]);

    $this->actingAs($admin)
        ->from(route('admin.listening.tests.sections.show', [$test, $section]))
        ->post(route('admin.listening.tests.sections.transcript.attach', [$test, $section]), [
            'transcript_id' => $transcript->id,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('transcript_id');
});

it('accepts valid timestamped transcript structure', function (): void {
    $admin = createListeningTranscriptAdmin();
    $transcript = ListeningTranscript::query()->create(array_merge(validTranscriptPayload(), [
        'created_by' => $admin->id,
    ]));

    $payload = [
        ['line' => 1, 'speaker' => 'Man', 'start' => 0.00, 'end' => 4.50, 'text' => 'Good morning.'],
        ['line' => 2, 'speaker' => 'Woman', 'start' => 4.51, 'end' => 8.00, 'text' => 'I would like to book a room.'],
    ];

    $this->actingAs($admin)
        ->put(route('admin.listening.transcripts.timestamps.update', $transcript), [
            'timestamped_transcript' => $payload,
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($transcript->fresh()?->timestamped_transcript)->toHaveCount(2);
});

it('rejects invalid timestamped transcript', function (): void {
    $admin = createListeningTranscriptAdmin();
    $transcript = ListeningTranscript::query()->create(array_merge(validTranscriptPayload(), [
        'created_by' => $admin->id,
    ]));

    $this->actingAs($admin)
        ->from(route('admin.listening.transcripts.show', $transcript))
        ->put(route('admin.listening.transcripts.timestamps.update', $transcript), [
            'timestamped_transcript' => [
                ['line' => 1, 'start' => 5.00, 'end' => 2.00, 'text' => 'Bad timing'],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('timestamped_transcript');
});

it('rejects overlapping timestamps', function (): void {
    $admin = createListeningTranscriptAdmin();
    $transcript = ListeningTranscript::query()->create(array_merge(validTranscriptPayload(), [
        'created_by' => $admin->id,
    ]));

    $this->actingAs($admin)
        ->from(route('admin.listening.transcripts.show', $transcript))
        ->put(route('admin.listening.transcripts.timestamps.update', $transcript), [
            'timestamped_transcript' => [
                ['line' => 1, 'start' => 0.00, 'end' => 5.00, 'text' => 'First line'],
                ['line' => 2, 'start' => 4.00, 'end' => 8.00, 'text' => 'Overlapping line'],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('timestamped_transcript');
});

it('defaults transcript visibility to admin only', function (): void {
    $admin = createListeningTranscriptAdmin();

    $this->actingAs($admin)
        ->post(route('admin.listening.transcripts.store'), validTranscriptPayload([
            'visibility' => ListeningTranscriptVisibility::AdminOnly->value,
        ]))
        ->assertRedirect();

    $transcript = ListeningTranscript::query()->latest('id')->first();
    expect($transcript?->visibility)->toBe(ListeningTranscriptVisibility::AdminOnly);
});

it('does not treat review_visible as live-test visible', function (): void {
    $admin = createListeningTranscriptAdmin();
    $transcript = ListeningTranscript::query()->create(array_merge(validTranscriptPayload([
        'visibility' => ListeningTranscriptVisibility::ReviewVisible->value,
    ]), [
        'created_by' => $admin->id,
    ]));

    $futureReview = app(\App\Services\Listening\ListeningPassageService::class)->prepareForFutureReview($transcript);

    expect($futureReview['never_visible_during_live_test'])->toBeTrue()
        ->and($futureReview['may_show_after_submit'])->toBeTrue();
});

it('forbids unauthorized user from managing transcripts', function (): void {
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'student-transcripts-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($student)
        ->get(route('admin.listening.transcripts.index'))
        ->assertForbidden();
});

it('includes transcript status in section readiness', function (): void {
    $admin = createListeningTranscriptAdmin();
    $test = createListeningTestForTranscripts($admin);
    $audio = createListeningAudioForTranscripts($admin);
    $transcript = ListeningTranscript::query()->create(array_merge(validTranscriptPayload([
        'visibility' => ListeningTranscriptVisibility::ReviewVisible->value,
        'timestamped_transcript' => [
            ['line' => 1, 'start' => 0.0, 'end' => 2.0, 'text' => 'Hello'],
        ],
    ]), [
        'listening_audio_id' => $audio->id,
        'created_by' => $admin->id,
    ]));

    $section = ListeningSection::query()->create([
        'listening_test_id' => $test->id,
        'section_number' => 1,
        'title' => 'Section 1',
        'section_type' => 'conversation',
        'audio_id' => $audio->id,
        'transcript_id' => $transcript->id,
        'start_question_number' => 1,
        'end_question_number' => 10,
        'total_questions' => 10,
        'display_order' => 1,
        'is_active' => true,
    ]);

    $readiness = app(ListeningSectionService::class)->getSectionReadiness($section);

    expect($readiness['has_transcript'])->toBeTrue()
        ->and($readiness['has_timestamped_transcript'])->toBeTrue()
        ->and($readiness['transcript_audio_matches'])->toBeTrue()
        ->and($readiness['transcript_visibility'])->toBe('review_visible')
        ->and($readiness['transcript_ready_for_review'])->toBeTrue();
});
