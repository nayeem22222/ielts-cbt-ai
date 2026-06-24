<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\ReadingQuestionType;
use App\Models\ExamTest;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\ReadingTest;
use App\Services\Admin\Exam\ReadingTestBuilderService;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    seedRbac();
});

it('allows admin to view reading tests index', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'reading-tests-admin@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.reading-tests.index'))
        ->assertOk()
        ->assertSee('Reading Test Directory');
});

it('creates reading test settings without bootstrapping legacy builder data', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'reading-tests-create@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)->post(route('admin.reading-tests.store'), [
        'title' => 'Academic Reading Mock 1',
        'slug' => 'academic-reading-mock-1',
        'exam_type' => ExamType::Academic->value,
        'duration_minutes' => 60,
        'instructions' => 'Full reading practice test',
        'status' => PublishStatus::Draft->value,
    ])->assertRedirect();

    $test = ReadingTest::query()->where('slug', 'academic-reading-mock-1')->first();

    expect($test)->not->toBeNull();
    expect($test->duration_minutes)->toBe(60);
    expect($test->created_by)->toBe($admin->id);
    expect(ExamTest::query()->where('slug', 'academic-reading-mock-1')->exists())->toBeFalse();
});

it('duplicates reading test content tree as draft', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'reading-builder@example.com',
        'email_verified_at' => now(),
    ]);

    $test = ReadingTest::query()->create([
        'title' => 'Builder Test',
        'slug' => 'builder-test',
        'exam_type' => ExamType::Academic,
        'duration_minutes' => 60,
        'status' => PublishStatus::Published,
        'published_at' => now(),
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $passage = $test->passages()->create([
        'title' => 'Passage 1',
        'part_number' => 1,
        'instruction' => 'Answer questions 1-3.',
        'content_text' => 'Climate change affects ecosystems worldwide.',
        'sort_order' => 1,
    ]);

    $group = $passage->groups()->create([
        'title' => 'Questions 1-1',
        'instruction' => 'Do the following statements agree?',
        'question_type' => 'true_false_not_given',
        'start_question' => 1,
        'end_question' => 1,
        'sort_order' => 1,
    ]);

    $question = $group->questions()->create([
        'question_number' => 1,
        'prompt' => 'The passage mentions reversible climate effects.',
        'marks' => 1,
        'sort_order' => 1,
    ]);
    $question->options()->create(['option_key' => 'T', 'option_label' => 'True', 'sort_order' => 1]);
    $question->correctAnswers()->create(['answer' => 'False']);

    $this->actingAs($admin)
        ->post(route('admin.reading-tests.duplicate', $test))
        ->assertRedirect();

    $copy = ReadingTest::query()->where('slug', 'copy-of-builder-test')->firstOrFail();

    expect($copy->title)->toBe('Copy of Builder Test');
    expect($copy->status)->toBe(PublishStatus::Draft);
    expect($copy->published_at)->toBeNull();
    expect($copy->passages()->count())->toBe(1);
    expect($copy->questionGroups()->count())->toBe(1);
    expect($copy->questions_count)->toBe(1);
});

it('supports all fifteen official reading question types', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'reading-types@example.com',
        'email_verified_at' => now(),
    ]);

    $builder = app(ReadingTestBuilderService::class);
    $test = $builder->importTest([
        'test' => [
            'title' => 'Types Test',
            'slug' => 'types-test',
            'exam_type' => ExamType::Academic->value,
        ],
        'passages' => [[
            'title' => 'Passage',
            'sort_order' => 1,
            'stimulus_text' => 'Sample passage.',
            'questions' => [],
        ]],
    ], $admin);

    $module = $builder->readingModule($test);
    $section = $module->sections()->firstOrFail();
    $bank = $builder->questionBank($test);

    foreach (ReadingQuestionType::cases() as $index => $type) {
        $builder->saveQuestion($test, $module, $section, $bank, [
            'type' => $type->value,
            'question_number' => $index + 1,
            'prompt' => "Prompt for {$type->label()}",
            'options' => $type->usesOptions() ? ['Option A', 'Option B', 'Option C'] : [],
            'correct_answer' => $type->usesOptions() ? 'Option A' : 'keyword',
            'marks' => 1,
            'sort_order' => $index + 1,
        ]);
    }

    expect(Question::query()->where('question_bank_id', $bank->id)->count())->toBe(15);
    expect(ReadingQuestionType::cases())->toHaveCount(15);
});

it('exports reading tests csv from admin list', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'reading-import-export@example.com',
        'email_verified_at' => now(),
    ]);

    ReadingTest::query()->create([
        'title' => 'Export Test',
        'slug' => 'export-test',
        'exam_type' => ExamType::Academic,
        'duration_minutes' => 60,
        'status' => PublishStatus::Draft,
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.reading-tests.export'));

    $response->assertOk();
    expect($response->streamedContent())->toContain('Export Test');
});

it('shows reading test preview to admin', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'reading-preview@example.com',
        'email_verified_at' => now(),
    ]);

    $test = ReadingTest::query()->create([
        'title' => 'Preview Test',
        'slug' => 'preview-test',
        'exam_type' => ExamType::Academic,
        'duration_minutes' => 60,
        'status' => PublishStatus::Draft,
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.reading-tests.preview', $test))
        ->assertOk()
        ->assertSee('Admin Preview')
        ->assertSee('Preview Test')
        ->assertSee('preview-test');
});

it('manages question banks with csv import support', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'question-banks@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.question-banks.index'))
        ->assertOk()
        ->assertSee('Question Bank Directory');

    $this->actingAs($admin)->post(route('admin.question-banks.store'), [
        'name' => 'Academic Reading Pool',
        'slug' => 'academic-reading-pool',
        'description' => 'Shared reading items',
        'exam_type' => ExamType::Academic->value,
        'status' => PublishStatus::Draft->value,
    ])->assertRedirect(route('admin.question-banks.index'));

    $bank = QuestionBank::query()->where('slug', 'academic-reading-pool')->first();

    expect($bank)->not->toBeNull();
    expect($bank->module)->toBe('reading');
});

it('denies reading test builder access without permission', function (): void {
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'reading-student@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($student)
        ->get(route('admin.reading-tests.index'))
        ->assertForbidden();
});

it('opens builder after creating a reading test', function (): void {
    $this->withoutVite();

    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'reading-builder-ui@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)->post(route('admin.reading-tests.store'), [
        'title' => 'Builder UI Test',
        'slug' => 'builder-ui-test',
        'exam_type' => ExamType::Academic->value,
        'duration_minutes' => 60,
        'status' => PublishStatus::Draft->value,
    ]);

    $test = ReadingTest::query()->where('slug', 'builder-ui-test')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('admin.reading-tests.builder', $test))
        ->assertOk()
        ->assertSee('Reading Test Builder')
        ->assertSee('Add Passage')
        ->assertSee('Builder UI Test');
});

it('registers tests and question bank permissions', function (): void {
    expect(DB::table('permissions')->where('name', 'tests.view')->exists())->toBeTrue();
    expect(DB::table('permissions')->where('name', 'question_banks.view')->exists())->toBeTrue();
});
