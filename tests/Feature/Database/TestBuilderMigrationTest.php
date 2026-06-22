<?php

declare(strict_types=1);

use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('creates test builder tables with expected columns', function (): void {
    $tables = [
        'tests',
        'test_modules',
        'test_sections',
        'question_banks',
        'questions',
        'question_options',
        'question_correct_answers',
        'question_explanations',
        'question_tags',
        'test_question',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Missing table: {$table}");
    }

    expect(Schema::hasColumn('test_modules', 'module'))->toBeTrue();
    expect(Schema::hasColumn('questions', 'stimulus'))->toBeTrue();
    expect(Schema::hasColumn('test_question', 'marks'))->toBeTrue();
});

it('supports all four IELTS modules in a test blueprint', function (): void {
    $admin = createUserWithRole(\App\Enums\Auth\UserRole::Admin, [
        'email' => 'test-builder@example.com',
    ]);

    $testId = DB::table('tests')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'slug' => 'ielts-full-mock-1',
        'title' => 'IELTS Full Mock Test 1',
        'type' => 'full_mock',
        'exam_type' => 'academic',
        'duration_seconds' => 10800,
        'total_questions' => 4,
        'status' => 'published',
        'published_at' => now(),
        'created_by' => $admin->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $modules = ['reading', 'listening', 'writing', 'speaking'];
    $moduleIds = [];

    foreach ($modules as $index => $module) {
        $moduleIds[$module] = DB::table('test_modules')->insertGetId([
            'test_id' => $testId,
            'module' => $module,
            'title' => ucfirst($module).' Module',
            'sort_order' => $index + 1,
            'duration_seconds' => 1800,
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $bankId = DB::table('question_banks')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'slug' => 'reading-bank-1',
        'name' => 'Reading Bank 1',
        'module' => 'reading',
        'exam_type' => 'academic',
        'status' => 'published',
        'created_by' => $admin->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $questionId = DB::table('questions')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'question_bank_id' => $bankId,
        'module' => 'reading',
        'type' => 'true_false_ng',
        'question_number' => 1,
        'prompt' => 'The passage states that climate change is reversible.',
        'difficulty' => 'medium',
        'marks' => 1,
        'status' => 'published',
        'created_by' => $admin->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('question_options')->insert([
        ['question_id' => $questionId, 'label' => 'T', 'option_text' => 'True', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['question_id' => $questionId, 'label' => 'F', 'option_text' => 'False', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
        ['question_id' => $questionId, 'label' => 'NG', 'option_text' => 'Not Given', 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
    ]);

    DB::table('question_correct_answers')->insert([
        'question_id' => $questionId,
        'answer_key' => 'default',
        'answer_type' => 'text',
        'answer_value' => 'False',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('question_explanations')->insert([
        'question_id' => $questionId,
        'explanation' => 'Paragraph two indicates the process is not fully reversible.',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('question_tags')->insert([
        ['question_id' => $questionId, 'tag' => 'reading', 'created_at' => now(), 'updated_at' => now()],
        ['question_id' => $questionId, 'tag' => 'true-false-ng', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $sectionId = DB::table('test_sections')->insertGetId([
        'test_module_id' => $moduleIds['reading'],
        'title' => 'Reading Passage 1',
        'sort_order' => 1,
        'stimulus_text' => 'Sample passage content.',
        'status' => 'published',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('test_question')->insert([
        'test_id' => $testId,
        'test_module_id' => $moduleIds['reading'],
        'test_section_id' => $sectionId,
        'question_id' => $questionId,
        'sort_order' => 1,
        'marks' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('test_modules')->where('test_id', $testId)->count())->toBe(4)
        ->and(DB::table('test_modules')->where('test_id', $testId)->pluck('module')->sort()->values()->all())
        ->toBe(['listening', 'reading', 'speaking', 'writing'])
        ->and(DB::table('test_question')->where('test_id', $testId)->count())->toBe(1)
        ->and(DB::table('question_tags')->where('question_id', $questionId)->count())->toBe(2);
});

it('enforces one module per skill on each test', function (): void {
    $testId = DB::table('tests')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'slug' => 'duplicate-module-test',
        'title' => 'Duplicate Module Test',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('test_modules')->insert([
        'test_id' => $testId,
        'module' => 'reading',
        'title' => 'Reading',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('test_modules')->insert([
        'test_id' => $testId,
        'module' => 'reading',
        'title' => 'Reading Duplicate',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});
