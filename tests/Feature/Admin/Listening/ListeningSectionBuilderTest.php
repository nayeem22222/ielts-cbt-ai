<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Listening\ListeningDifficultyLevel;
use App\Enums\Listening\ListeningSectionType;
use App\Enums\Listening\ListeningTestStatus;
use App\Enums\Listening\ListeningTestType;
use App\Models\Listening\ListeningAudio;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Models\Listening\ListeningTranscript;
use App\Models\User;
use App\Services\Listening\ListeningSectionService;

beforeEach(function (): void {
    seedRbac();
});

function createListeningSectionAdmin(): User
{
    return createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'listening-sections-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);
}

function createListeningTestForSections(User $admin, array $overrides = []): ListeningTest
{
    return ListeningTest::query()->create(array_merge([
        'title' => 'Section Builder Test '.uniqid(),
        'slug' => 'section-builder-'.uniqid(),
        'test_code' => 'LST-'.strtoupper(uniqid()),
        'test_type' => ListeningTestType::Academic,
        'difficulty_level' => ListeningDifficultyLevel::Official,
        'duration_minutes' => 30,
        'status' => ListeningTestStatus::Draft,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ], $overrides));
}

function validSectionPayload(int $sectionNumber = 1, array $overrides = []): array
{
    return array_merge([
        'section_number' => $sectionNumber,
        'title' => 'Section '.$sectionNumber,
        'section_type' => match ($sectionNumber) {
            1 => ListeningSectionType::Conversation->value,
            2 => ListeningSectionType::Monologue->value,
            3 => ListeningSectionType::AcademicDiscussion->value,
            default => ListeningSectionType::Lecture->value,
        },
        'is_active' => true,
    ], $overrides);
}

it('allows admin to view listening section list', function (): void {
    $admin = createListeningSectionAdmin();
    $test = createListeningTestForSections($admin);

    $this->actingAs($admin)
        ->get(route('admin.listening.tests.sections.index', $test))
        ->assertOk()
        ->assertSee('Listening Sections');
});

it('allows admin to auto-create default 4 sections', function (): void {
    $admin = createListeningSectionAdmin();
    $test = createListeningTestForSections($admin);

    $this->actingAs($admin)
        ->post(route('admin.listening.tests.sections.default', $test))
        ->assertRedirect();

    expect($test->sections()->count())->toBe(4);
    expect($test->sections()->where('section_number', 1)->first()?->start_question_number)->toBe(1);
    expect($test->sections()->where('section_number', 4)->first()?->end_question_number)->toBe(40);
});

it('does not duplicate sections when auto-creating defaults', function (): void {
    $admin = createListeningSectionAdmin();
    $test = createListeningTestForSections($admin);

    $test->sections()->create([
        'section_number' => 1,
        'title' => 'Section 1',
        'section_type' => ListeningSectionType::Conversation,
        'start_question_number' => 1,
        'end_question_number' => 10,
        'total_questions' => 10,
        'display_order' => 1,
        'is_active' => true,
    ]);

    $this->actingAs($admin)->post(route('admin.listening.tests.sections.default', $test));

    expect($test->sections()->count())->toBe(4);
    expect($test->sections()->where('section_number', 1)->count())->toBe(1);
});

it('allows admin to create one section manually', function (): void {
    $admin = createListeningSectionAdmin();
    $test = createListeningTestForSections($admin);

    $this->actingAs($admin)
        ->post(route('admin.listening.tests.sections.store', $test), validSectionPayload(2))
        ->assertRedirect();

    $section = $test->sections()->where('section_number', 2)->first();
    expect($section)->not->toBeNull();
    expect($section->start_question_number)->toBe(11);
    expect($section->end_question_number)->toBe(20);
});

it('rejects duplicate section number', function (): void {
    $admin = createListeningSectionAdmin();
    $test = createListeningTestForSections($admin);

    $test->sections()->create(array_merge(validSectionPayload(1), [
        'start_question_number' => 1,
        'end_question_number' => 10,
        'total_questions' => 10,
        'display_order' => 1,
    ]));

    $this->actingAs($admin)
        ->from(route('admin.listening.tests.sections.create', $test))
        ->post(route('admin.listening.tests.sections.store', $test), validSectionPayload(1))
        ->assertSessionHasErrors('section_number');
});

it('rejects section number outside 1-4', function (): void {
    $admin = createListeningSectionAdmin();
    $test = createListeningTestForSections($admin);

    $this->actingAs($admin)
        ->from(route('admin.listening.tests.sections.create', $test))
        ->post(route('admin.listening.tests.sections.store', $test), validSectionPayload(1, ['section_number' => 5]))
        ->assertSessionHasErrors('section_number');
});

it('assigns correct official question range from section number', function (): void {
    $admin = createListeningSectionAdmin();
    $test = createListeningTestForSections($admin);

    $this->actingAs($admin)->post(route('admin.listening.tests.sections.store', $test), validSectionPayload(3));

    $section = $test->sections()->where('section_number', 3)->firstOrFail();
    expect($section->start_question_number)->toBe(21);
    expect($section->end_question_number)->toBe(30);
    expect($section->total_questions)->toBe(10);
});

it('allows admin to update section', function (): void {
    $admin = createListeningSectionAdmin();
    $test = createListeningTestForSections($admin);
    $section = $test->sections()->create([
        'section_number' => 1,
        'title' => 'Old Title',
        'section_type' => ListeningSectionType::Conversation,
        'start_question_number' => 1,
        'end_question_number' => 10,
        'total_questions' => 10,
        'display_order' => 1,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.listening.tests.sections.update', [$test, $section]), validSectionPayload(1, ['title' => 'Updated Title']))
        ->assertRedirect();

    expect($section->fresh()->title)->toBe('Updated Title');
});

it('allows admin to delete section', function (): void {
    $admin = createListeningSectionAdmin();
    $test = createListeningTestForSections($admin);
    $section = $test->sections()->create([
        'section_number' => 2,
        'title' => 'Section 2',
        'section_type' => ListeningSectionType::Monologue,
        'start_question_number' => 11,
        'end_question_number' => 20,
        'total_questions' => 10,
        'display_order' => 2,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.listening.tests.sections.destroy', [$test, $section]))
        ->assertRedirect(route('admin.listening.tests.sections.index', $test));

    expect(ListeningSection::query()->whereKey($section->id)->exists())->toBeFalse();
});

it('allows admin to restore section', function (): void {
    $admin = createListeningSectionAdmin();
    $test = createListeningTestForSections($admin);
    $section = $test->sections()->create([
        'section_number' => 3,
        'title' => 'Section 3',
        'section_type' => ListeningSectionType::AcademicDiscussion,
        'start_question_number' => 21,
        'end_question_number' => 30,
        'total_questions' => 10,
        'display_order' => 3,
        'is_active' => true,
    ]);
    $section->delete();

    $this->actingAs($admin)
        ->post(route('admin.listening.tests.sections.restore', [$test, $section->id]))
        ->assertRedirect(route('admin.listening.tests.sections.index', $test));

    expect(ListeningSection::query()->whereKey($section->id)->exists())->toBeTrue();
});

it('rejects section access when section belongs to another test', function (): void {
    $admin = createListeningSectionAdmin();
    $testA = createListeningTestForSections($admin);
    $testB = createListeningTestForSections($admin);
    $section = $testB->sections()->create([
        'section_number' => 1,
        'title' => 'Other Test Section',
        'section_type' => ListeningSectionType::Conversation,
        'start_question_number' => 1,
        'end_question_number' => 10,
        'total_questions' => 10,
        'display_order' => 1,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.listening.tests.sections.show', [$testA, $section]))
        ->assertRedirect(route('admin.listening.tests.sections.index', $testA))
        ->assertSessionHas('error');
});

it('allows admin to reorder sections', function (): void {
    $admin = createListeningSectionAdmin();
    $test = createListeningTestForSections($admin);

    $sectionA = $test->sections()->create([
        'section_number' => 1,
        'title' => 'Section 1',
        'section_type' => ListeningSectionType::Conversation,
        'start_question_number' => 1,
        'end_question_number' => 10,
        'total_questions' => 10,
        'display_order' => 1,
        'is_active' => true,
    ]);

    $sectionB = $test->sections()->create([
        'section_number' => 2,
        'title' => 'Section 2',
        'section_type' => ListeningSectionType::Monologue,
        'start_question_number' => 11,
        'end_question_number' => 20,
        'total_questions' => 10,
        'display_order' => 2,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.listening.tests.sections.reorder', $test), [
            'sections' => [$sectionB->id, $sectionA->id],
        ])
        ->assertRedirect();

    expect($sectionB->fresh()->display_order)->toBe(1);
    expect($sectionA->fresh()->display_order)->toBe(2);
});

it('rejects reordering with section from another test', function (): void {
    $admin = createListeningSectionAdmin();
    $testA = createListeningTestForSections($admin);
    $testB = createListeningTestForSections($admin);

    $foreignSection = $testB->sections()->create([
        'section_number' => 1,
        'title' => 'Foreign',
        'section_type' => ListeningSectionType::Conversation,
        'start_question_number' => 1,
        'end_question_number' => 10,
        'total_questions' => 10,
        'display_order' => 1,
        'is_active' => true,
    ]);

    $localSection = $testA->sections()->create([
        'section_number' => 1,
        'title' => 'Local',
        'section_type' => ListeningSectionType::Conversation,
        'start_question_number' => 1,
        'end_question_number' => 10,
        'total_questions' => 10,
        'display_order' => 1,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.listening.tests.sections.index', $testA))
        ->post(route('admin.listening.tests.sections.reorder', $testA), [
            'sections' => [$localSection->id, $foreignSection->id],
        ])
        ->assertSessionHasErrors('sections');
});

it('shows missing audio in readiness summary', function (): void {
    $admin = createListeningSectionAdmin();
    $test = createListeningTestForSections($admin);
    $section = $test->sections()->create([
        'section_number' => 1,
        'title' => 'Section 1',
        'section_type' => ListeningSectionType::Conversation,
        'start_question_number' => 1,
        'end_question_number' => 10,
        'total_questions' => 10,
        'display_order' => 1,
        'is_active' => true,
    ]);

    $readiness = app(ListeningSectionService::class)->getSectionReadiness($section);

    expect($readiness['has_audio'])->toBeFalse();
    expect($readiness['is_ready'])->toBeFalse();
    expect($readiness['missing'])->toContain('Section audio is missing.');
});

it('denies unauthorized user from managing sections', function (): void {
    $admin = createListeningSectionAdmin();
    $test = createListeningTestForSections($admin);
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'section-student-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($student)
        ->get(route('admin.listening.tests.sections.index', $test))
        ->assertForbidden();
});

it('can attach audio and transcript to section', function (): void {
    $admin = createListeningSectionAdmin();
    $test = createListeningTestForSections($admin);

    $audio = ListeningAudio::query()->create([
        'original_name' => 'section-1.mp3',
        'stored_name' => 'section-1.mp3',
        'disk' => 'local',
        'path' => 'listening/section-1.mp3',
        'processing_status' => 'completed',
        'validation_status' => 'valid',
        'uploaded_by' => $admin->id,
    ]);

    $transcript = ListeningTranscript::query()->create([
        'listening_audio_id' => $audio->id,
        'title' => 'Section 1 Transcript',
        'transcript_text' => 'Hello world',
        'visibility' => 'admin_only',
        'is_official' => true,
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.listening.tests.sections.store', $test), validSectionPayload(1, [
            'audio_id' => $audio->id,
            'transcript_id' => $transcript->id,
        ]))
        ->assertRedirect();

    $section = $test->sections()->where('section_number', 1)->firstOrFail();
    expect($section->audio_id)->toBe($audio->id);
    expect($section->transcript_id)->toBe($transcript->id);
});
