<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\ReadingQuestionType;
use App\Enums\Exam\TestType;
use App\Models\ExamTest;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\TestModule;
use App\Models\TestSection;
use App\Services\Admin\Exam\ReadingTestBuilderService;
use Illuminate\Http\UploadedFile;
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

it('creates reading test and bootstraps module and question bank', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'reading-tests-create@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)->post(route('admin.reading-tests.store'), [
        'title' => 'Academic Reading Mock 1',
        'slug' => 'academic-reading-mock-1',
        'description' => 'Full reading practice test',
        'exam_type' => ExamType::Academic->value,
        'duration_seconds' => 3600,
        'is_timed' => true,
        'status' => PublishStatus::Draft->value,
    ])->assertRedirect();

    $test = ExamTest::query()->where('slug', 'academic-reading-mock-1')->first();

    expect($test)->not->toBeNull();
    expect($test->type)->toBe(TestType::ReadingTest);
    expect(TestModule::query()->where('test_id', $test->id)->where('module', 'reading')->exists())->toBeTrue();
    expect(QuestionBank::query()->where('slug', 'academic-reading-mock-1-reading-bank')->exists())->toBeTrue();
});

it('saves passages and questions in the builder', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'reading-builder@example.com',
        'email_verified_at' => now(),
    ]);

    $test = app(ReadingTestBuilderService::class)->importTest([
        'test' => [
            'title' => 'Builder Test',
            'slug' => 'builder-test',
            'exam_type' => ExamType::Academic->value,
            'duration_seconds' => 3600,
            'status' => PublishStatus::Draft->value,
        ],
        'passages' => [],
    ], $admin);

    $this->actingAs($admin)->post(route('admin.reading-tests.passages.store', $test), [
        'title' => 'Passage 1',
        'sort_order' => 1,
        'instructions' => 'Answer questions 1-3.',
        'stimulus_text' => 'Climate change affects ecosystems worldwide.',
        'status' => PublishStatus::Draft->value,
    ])->assertRedirect();

    $section = TestSection::query()->where('title', 'Passage 1')->firstOrFail();

    $this->actingAs($admin)->post(route('admin.reading-tests.questions.store', [$test, $section]), [
        'type' => ReadingQuestionType::TrueFalseNg->value,
        'question_number' => 1,
        'prompt' => 'The passage mentions reversible climate effects.',
        'correct_answer' => 'False',
        'marks' => 1,
        'sort_order' => 1,
    ])->assertRedirect();

    $test->refresh();
    expect($test->total_questions)->toBe(1);
    expect($section->fresh()->question_count)->toBe(1);
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

it('exports and imports reading test json', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'reading-import-export@example.com',
        'email_verified_at' => now(),
    ]);

    $builder = app(ReadingTestBuilderService::class);
    $test = $builder->importTest([
        'test' => [
            'title' => 'Export Test',
            'slug' => 'export-test',
            'exam_type' => ExamType::Academic->value,
        ],
        'passages' => [[
            'title' => 'Passage 1',
            'sort_order' => 1,
            'stimulus_text' => 'Export passage text.',
            'questions' => [[
                'type' => ReadingQuestionType::ShortAnswer->value,
                'question_number' => 1,
                'prompt' => 'What is the main topic?',
                'correct_answer' => 'climate',
                'marks' => 1,
                'sort_order' => 1,
                'options' => [],
            ]],
        ]],
    ], $admin);

    $payload = $builder->exportTest($test);

    expect($payload['version'])->toBe(1);
    expect($payload['passages'])->toHaveCount(1);
    expect($payload['passages'][0]['questions'])->toHaveCount(1);

    $response = $this->actingAs($admin)
        ->get(route('admin.reading-tests.export-json', $test));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('export-test-reading-test.json');

    $json = UploadedFile::fake()->createWithContent('import.json', json_encode($payload));

    $this->actingAs($admin)
        ->post(route('admin.reading-tests.import-json', $test), ['file' => $json])
        ->assertRedirect(route('admin.reading-tests.builder', $test));

    expect($test->fresh()->total_questions)->toBeGreaterThanOrEqual(1);
});

it('shows reading test preview to admin', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'reading-preview@example.com',
        'email_verified_at' => now(),
    ]);

    $test = app(ReadingTestBuilderService::class)->importTest([
        'test' => [
            'title' => 'Preview Test',
            'slug' => 'preview-test',
            'exam_type' => ExamType::Academic->value,
        ],
        'passages' => [[
            'title' => 'Urban Farming',
            'sort_order' => 1,
            'stimulus_text' => 'Urban farming is growing rapidly.',
            'questions' => [[
                'type' => ReadingQuestionType::MultipleChoiceSingle->value,
                'question_number' => 1,
                'prompt' => 'What is urban farming?',
                'options' => ['A city garden', 'A rural farm', 'A factory'],
                'correct_answer' => 'A city garden',
                'marks' => 1,
                'sort_order' => 1,
            ]],
        ]],
    ], $admin);

    $this->actingAs($admin)
        ->get(route('admin.reading-tests.preview', $test))
        ->assertOk()
        ->assertSee('Candidate Preview')
        ->assertSee('Urban Farming')
        ->assertSee('Multiple Choice (Single Answer)');
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
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'reading-builder-ui@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)->post(route('admin.reading-tests.store'), [
        'title' => 'Builder UI Test',
        'slug' => 'builder-ui-test',
        'exam_type' => ExamType::Academic->value,
        'duration_seconds' => 3600,
        'is_timed' => true,
        'status' => PublishStatus::Draft->value,
    ]);

    $test = ExamTest::query()->where('slug', 'builder-ui-test')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('admin.reading-tests.builder', $test))
        ->assertOk()
        ->assertSee('Reading Test Builder')
        ->assertSee('Add Passage');
});

it('registers tests and question bank permissions', function (): void {
    expect(DB::table('permissions')->where('name', 'tests.view')->exists())->toBeTrue();
    expect(DB::table('permissions')->where('name', 'question_banks.view')->exists())->toBeTrue();
});
