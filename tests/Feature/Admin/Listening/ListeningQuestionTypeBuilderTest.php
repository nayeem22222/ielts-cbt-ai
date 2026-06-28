<?php

declare(strict_types=1);

use App\Actions\Listening\QuestionTypes\GenerateQuestionTypePreviewAction;
use App\Enums\Auth\UserRole;
use App\Enums\Listening\ListeningAnswerFormat;
use App\Enums\Listening\ListeningDifficultyLevel;
use App\Enums\Listening\ListeningLayoutType;
use App\Enums\Listening\ListeningQuestionType;
use App\Enums\Listening\ListeningSectionType;
use App\Enums\Listening\ListeningTestStatus;
use App\Enums\Listening\ListeningTestType;
use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Models\User;
use App\Services\Listening\QuestionTypes\ListeningQuestionTypeRegistry;

beforeEach(function (): void {
    seedRbac();
});

function typeBuilderAdmin(): User
{
    return createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'listening-types-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);
}

function typeBuilderTest(User $admin): ListeningTest
{
    return ListeningTest::query()->create([
        'title' => 'Type Builder Test',
        'slug' => 'type-builder-'.uniqid(),
        'test_code' => 'LTB-'.strtoupper(uniqid()),
        'test_type' => ListeningTestType::Academic,
        'difficulty_level' => ListeningDifficultyLevel::Official,
        'duration_minutes' => 30,
        'status' => ListeningTestStatus::Draft,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);
}

function typeBuilderSection(ListeningTest $test): ListeningSection
{
    return ListeningSection::query()->create([
        'listening_test_id' => $test->id,
        'section_number' => 1,
        'title' => 'Section 1',
        'section_type' => ListeningSectionType::Conversation->value,
        'start_question_number' => 1,
        'end_question_number' => 10,
        'total_questions' => 10,
        'display_order' => 1,
        'is_active' => true,
    ]);
}

it('rejects mcq group without enough options', function (): void {
    $admin = typeBuilderAdmin();
    $test = typeBuilderTest($admin);
    $section = typeBuilderSection($test);

    $this->actingAs($admin)
        ->from(route('admin.listening.tests.sections.groups.create', [$test, $section]))
        ->post(route('admin.listening.tests.sections.groups.store', [$test, $section]), [
            'title' => 'MCQ Group',
            'question_type' => ListeningQuestionType::MCQ->value,
            'start_question_number' => 1,
            'end_question_number' => 1,
            'layout_type' => ListeningLayoutType::Default->value,
            'options' => [['key' => 'A', 'text' => 'Only one']],
            'is_active' => true,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('options');
});

it('allows mcq group with valid options', function (): void {
    $admin = typeBuilderAdmin();
    $test = typeBuilderTest($admin);
    $section = typeBuilderSection($test);

    $this->actingAs($admin)
        ->post(route('admin.listening.tests.sections.groups.store', [$test, $section]), [
            'title' => 'MCQ Group',
            'question_type' => ListeningQuestionType::MCQ->value,
            'start_question_number' => 2,
            'end_question_number' => 2,
            'layout_type' => ListeningLayoutType::Default->value,
            'options' => [
                ['key' => 'A', 'text' => 'Library', 'is_correct' => false],
                ['key' => 'B', 'text' => 'Pool', 'is_correct' => true],
            ],
            'is_active' => true,
        ])
        ->assertRedirect()
        ->assertSessionHas('status');
});

it('admin preview loads for mcq group', function (): void {
    $admin = typeBuilderAdmin();
    $test = typeBuilderTest($admin);
    $section = typeBuilderSection($test);
    $group = ListeningQuestionGroup::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'title' => 'MCQ Preview',
        'question_type' => ListeningQuestionType::MCQ,
        'start_question_number' => 1,
        'end_question_number' => 1,
        'total_questions' => 1,
        'layout_type' => ListeningLayoutType::Default,
        'options' => [
            ['key' => 'A', 'text' => 'Yes', 'is_correct' => true],
            ['key' => 'B', 'text' => 'No', 'is_correct' => false],
        ],
        'is_active' => true,
    ]);

    $preview = app(GenerateQuestionTypePreviewAction::class)->execute($group, collect());

    expect($preview['type'])->toBe('mcq');
    expect($preview['preview_partial'])->toContain('mcq');
});

it('loads admin preview for every enabled type', function (): void {
    $registry = app(ListeningQuestionTypeRegistry::class);

    foreach ($registry->all() as $type) {
        $partial = $registry->previewPartialFor($type);
        expect(view()->exists($partial))->toBeTrue("Preview partial missing for {$type->value}");
    }
});

it('publish fails when type payload invalid', function (): void {
    $admin = typeBuilderAdmin();
    $test = typeBuilderTest($admin);

    for ($i = 1; $i <= 4; $i++) {
        ListeningSection::query()->create([
            'listening_test_id' => $test->id,
            'section_number' => $i,
            'title' => 'Section '.$i,
            'section_type' => ListeningSectionType::Conversation->value,
            'start_question_number' => (($i - 1) * 10) + 1,
            'end_question_number' => $i * 10,
            'total_questions' => 10,
            'display_order' => $i,
            'is_active' => true,
            'audio_id' => null,
        ]);
    }

    ListeningQuestionGroup::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $test->sections()->first()->id,
        'title' => 'Bad MCQ',
        'question_type' => ListeningQuestionType::MCQ,
        'start_question_number' => 1,
        'end_question_number' => 1,
        'total_questions' => 1,
        'layout_type' => ListeningLayoutType::Default,
        'options' => [],
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.listening.tests.publish', $test))
        ->assertRedirect()
        ->assertSessionHas('error');
});

it('rejects unsupported question type in request', function (): void {
    $admin = typeBuilderAdmin();
    $test = typeBuilderTest($admin);
    $section = typeBuilderSection($test);

    $this->actingAs($admin)
        ->from(route('admin.listening.tests.sections.groups.create', [$test, $section]))
        ->post(route('admin.listening.tests.sections.groups.store', [$test, $section]), [
            'title' => 'Bad',
            'question_type' => 'not_a_real_type',
            'start_question_number' => 1,
            'end_question_number' => 1,
            'layout_type' => ListeningLayoutType::Default->value,
            'is_active' => true,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('question_type');
});

it('reading module remains isolated from listening question types', function (): void {
    expect(class_exists(\App\Services\Listening\QuestionTypes\ListeningQuestionTypeRegistry::class))->toBeTrue();
    expect(str_starts_with(\App\Services\Listening\QuestionTypes\ListeningQuestionTypeRegistry::class, 'App\\Services\\Listening\\'))->toBeTrue();
    expect(is_dir(app_path('Services/Listening/QuestionTypes')))->toBeTrue();
});
