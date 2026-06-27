<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Listening\ListeningDifficultyLevel;
use App\Enums\Listening\ListeningTestStatus;
use App\Enums\Listening\ListeningTestType;
use App\Models\Listening\ListeningTest;
use App\Models\Listening\ListeningTestSetting;
use App\Models\User;

beforeEach(function (): void {
    seedRbac();
});

function createListeningTestAdmin(): User
{
    return createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'listening-crud-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);
}

function validListeningTestPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'IELTS Listening Mock '.uniqid(),
        'test_type' => ListeningTestType::Academic->value,
        'difficulty_level' => ListeningDifficultyLevel::Official->value,
        'duration_minutes' => 30,
        'transfer_time_minutes' => 10,
        'status' => ListeningTestStatus::Draft->value,
    ], $overrides);
}

it('allows admin to view listening test list', function (): void {
    $admin = createListeningTestAdmin();

    $this->actingAs($admin)
        ->get(route('admin.listening.tests.index'))
        ->assertOk()
        ->assertSee('Listening Test Directory');
});

it('allows admin to create listening test', function (): void {
    $admin = createListeningTestAdmin();
    $payload = validListeningTestPayload(['slug' => 'listening-mock-'.uniqid()]);

    $this->actingAs($admin)
        ->post(route('admin.listening.tests.store'), $payload)
        ->assertRedirect();

    expect(ListeningTest::query()->where('slug', $payload['slug'])->exists())->toBeTrue();
});

it('creates default settings with listening test', function (): void {
    $admin = createListeningTestAdmin();
    $payload = validListeningTestPayload(['slug' => 'listening-settings-'.uniqid()]);

    $this->actingAs($admin)->post(route('admin.listening.tests.store'), $payload);

    $test = ListeningTest::query()->where('slug', $payload['slug'])->firstOrFail();

    expect($test->setting)->not->toBeNull();
    expect($test->setting->allow_audio_replay)->toBeFalse();
    expect($test->setting->enable_auto_save)->toBeTrue();
});

it('allows admin to update listening test', function (): void {
    $admin = createListeningTestAdmin();
    $test = ListeningTest::query()->create([
        'title' => 'Original Listening Test',
        'slug' => 'original-listening-'.uniqid(),
        'test_code' => 'LST-'.strtoupper(uniqid()),
        'test_type' => ListeningTestType::Academic,
        'difficulty_level' => ListeningDifficultyLevel::Official,
        'duration_minutes' => 30,
        'status' => ListeningTestStatus::Draft,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.listening.tests.update', $test), validListeningTestPayload([
            'title' => 'Updated Listening Test',
            'slug' => $test->slug,
            'test_code' => $test->test_code,
        ]))
        ->assertRedirect(route('admin.listening.tests.show', $test));

    expect($test->fresh()->title)->toBe('Updated Listening Test');
});

it('allows admin to delete listening test', function (): void {
    $admin = createListeningTestAdmin();
    $test = ListeningTest::query()->create([
        'title' => 'Delete Me',
        'slug' => 'delete-listening-'.uniqid(),
        'test_code' => 'LST-'.strtoupper(uniqid()),
        'test_type' => ListeningTestType::Academic,
        'difficulty_level' => ListeningDifficultyLevel::Official,
        'duration_minutes' => 30,
        'status' => ListeningTestStatus::Draft,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.listening.tests.destroy', $test))
        ->assertRedirect(route('admin.listening.tests.index'));

    expect(ListeningTest::query()->whereKey($test->id)->exists())->toBeFalse();
    expect(ListeningTest::withTrashed()->whereKey($test->id)->exists())->toBeTrue();
});

it('allows admin to restore listening test', function (): void {
    $admin = createListeningTestAdmin();
    $test = ListeningTest::query()->create([
        'title' => 'Restore Me',
        'slug' => 'restore-listening-'.uniqid(),
        'test_code' => 'LST-'.strtoupper(uniqid()),
        'test_type' => ListeningTestType::Academic,
        'difficulty_level' => ListeningDifficultyLevel::Official,
        'duration_minutes' => 30,
        'status' => ListeningTestStatus::Draft,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);
    $test->delete();

    $this->actingAs($admin)
        ->post(route('admin.listening.tests.restore', $test->id))
        ->assertRedirect(route('admin.listening.tests.index'));

    expect(ListeningTest::query()->whereKey($test->id)->exists())->toBeTrue();
});

it('allows admin to duplicate listening test', function (): void {
    $admin = createListeningTestAdmin();
    $test = ListeningTest::query()->create([
        'title' => 'Source Listening Test',
        'slug' => 'source-listening-'.uniqid(),
        'test_code' => 'LST-'.strtoupper(uniqid()),
        'test_type' => ListeningTestType::General,
        'difficulty_level' => ListeningDifficultyLevel::Hard,
        'duration_minutes' => 30,
        'status' => ListeningTestStatus::Published,
        'is_active' => true,
        'published_at' => now(),
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);
    $test->setting()->create(ListeningTestSetting::officialDefaults());

    $this->actingAs($admin)
        ->post(route('admin.listening.tests.duplicate', $test))
        ->assertRedirect();

    $copy = ListeningTest::query()->where('title', 'Source Listening Test Copy')->first();

    expect($copy)->not->toBeNull();
    expect($copy->setting)->not->toBeNull();
});

it('creates duplicate listening test as draft and inactive', function (): void {
    $admin = createListeningTestAdmin();
    $test = ListeningTest::query()->create([
        'title' => 'Published Listening',
        'slug' => 'published-listening-'.uniqid(),
        'test_code' => 'LST-'.strtoupper(uniqid()),
        'test_type' => ListeningTestType::Academic,
        'difficulty_level' => ListeningDifficultyLevel::Official,
        'duration_minutes' => 30,
        'status' => ListeningTestStatus::Published,
        'is_active' => true,
        'published_at' => now(),
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);
    $test->setting()->create(ListeningTestSetting::officialDefaults());

    $this->actingAs($admin)->post(route('admin.listening.tests.duplicate', $test));

    $copy = ListeningTest::query()->where('title', 'Published Listening Copy')->firstOrFail();

    expect($copy->status)->toBe(ListeningTestStatus::Draft);
    expect($copy->is_active)->toBeFalse();
    expect($copy->published_at)->toBeNull();
});

it('blocks publishing incomplete listening test', function (): void {
    $admin = createListeningTestAdmin();
    $test = ListeningTest::query()->create([
        'title' => 'Incomplete Listening',
        'slug' => 'incomplete-listening-'.uniqid(),
        'test_code' => 'LST-'.strtoupper(uniqid()),
        'test_type' => ListeningTestType::Academic,
        'difficulty_level' => ListeningDifficultyLevel::Official,
        'duration_minutes' => 30,
        'status' => ListeningTestStatus::Draft,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);
    $test->setting()->create(ListeningTestSetting::officialDefaults());

    $this->actingAs($admin)
        ->from(route('admin.listening.tests.show', $test))
        ->post(route('admin.listening.tests.publish', $test))
        ->assertRedirect(route('admin.listening.tests.show', $test))
        ->assertSessionHas('error');

    expect($test->fresh()->status)->toBe(ListeningTestStatus::Draft);
});

it('allows admin to update listening test settings', function (): void {
    $admin = createListeningTestAdmin();
    $test = ListeningTest::query()->create([
        'title' => 'Settings Listening',
        'slug' => 'settings-listening-'.uniqid(),
        'test_code' => 'LST-'.strtoupper(uniqid()),
        'test_type' => ListeningTestType::Academic,
        'difficulty_level' => ListeningDifficultyLevel::Official,
        'duration_minutes' => 30,
        'status' => ListeningTestStatus::Draft,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);
    $test->setting()->create(ListeningTestSetting::officialDefaults());

    $this->actingAs($admin)
        ->put(route('admin.listening.tests.settings.update', $test), [
            'allow_review_after_submit' => true,
            'show_correct_answer' => false,
            'show_transcript_after_submit' => true,
            'show_audio_review' => false,
            'allow_audio_replay' => false,
            'allow_audio_seek' => false,
            'auto_submit_on_timer_end' => true,
            'enable_tab_switch_detection' => true,
            'enable_copy_protection' => true,
            'enable_question_flagging' => true,
            'enable_auto_save' => true,
            'auto_save_interval_seconds' => 20,
        ])
        ->assertRedirect();

    expect($test->fresh()->setting?->show_correct_answer)->toBeFalse();
    expect($test->fresh()->setting?->auto_save_interval_seconds)->toBe(20);
});

it('denies unauthorized user access to listening test crud', function (): void {
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'listening-student-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($student)
        ->get(route('admin.listening.tests.index'))
        ->assertForbidden();
});

it('validates listening test enums on create', function (): void {
    $admin = createListeningTestAdmin();

    $this->actingAs($admin)
        ->from(route('admin.listening.tests.create'))
        ->post(route('admin.listening.tests.store'), validListeningTestPayload([
            'test_type' => 'invalid-type',
        ]))
        ->assertSessionHasErrors('test_type');
});

it('filters listening tests by search', function (): void {
    $admin = createListeningTestAdmin();

    ListeningTest::query()->create([
        'title' => 'Unique Searchable Alpha',
        'slug' => 'alpha-'.uniqid(),
        'test_code' => 'LST-ALPHA-'.uniqid(),
        'test_type' => ListeningTestType::Academic,
        'difficulty_level' => ListeningDifficultyLevel::Official,
        'duration_minutes' => 30,
        'status' => ListeningTestStatus::Draft,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    ListeningTest::query()->create([
        'title' => 'Another Test Beta',
        'slug' => 'beta-'.uniqid(),
        'test_code' => 'LST-BETA-'.uniqid(),
        'test_type' => ListeningTestType::Academic,
        'difficulty_level' => ListeningDifficultyLevel::Official,
        'duration_minutes' => 30,
        'status' => ListeningTestStatus::Draft,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.listening.tests.index', ['search' => 'Unique Searchable Alpha']))
        ->assertOk()
        ->assertSee('Unique Searchable Alpha')
        ->assertDontSee('Another Test Beta');
});

it('filters listening tests by status', function (): void {
    $admin = createListeningTestAdmin();

    ListeningTest::query()->create([
        'title' => 'Draft Listening Filter',
        'slug' => 'draft-filter-'.uniqid(),
        'test_code' => 'LST-DRAFT-'.uniqid(),
        'test_type' => ListeningTestType::Academic,
        'difficulty_level' => ListeningDifficultyLevel::Official,
        'duration_minutes' => 30,
        'status' => ListeningTestStatus::Draft,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    ListeningTest::query()->create([
        'title' => 'Archived Listening Filter',
        'slug' => 'archived-filter-'.uniqid(),
        'test_code' => 'LST-ARCH-'.uniqid(),
        'test_type' => ListeningTestType::Academic,
        'difficulty_level' => ListeningDifficultyLevel::Official,
        'duration_minutes' => 30,
        'status' => ListeningTestStatus::Archived,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.listening.tests.index', ['status' => ListeningTestStatus::Archived->value]))
        ->assertOk()
        ->assertSee('Archived Listening Filter')
        ->assertDontSee('Draft Listening Filter');
});
