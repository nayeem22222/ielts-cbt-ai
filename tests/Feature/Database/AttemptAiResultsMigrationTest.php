<?php

declare(strict_types=1);

use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('creates attempt ai results and report tables', function (): void {
    $tables = [
        'test_attempts',
        'student_answers',
        'autosave_logs',
        'ai_providers',
        'ai_models',
        'ai_prompt_templates',
        'ai_requests',
        'ai_responses',
        'ai_evaluation_scores',
        'ai_evaluation_mistakes',
        'results',
        'band_scores',
        'pdf_reports',
        'teacher_reviews',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Missing table: {$table}");
    }
});

it('supports attempt to ai evaluation to result report flow', function (): void {
    $student = createUserWithRole(\App\Enums\Auth\UserRole::Student, [
        'email' => 'attempt-student@example.com',
    ]);
    $teacher = createUserWithRole(\App\Enums\Auth\UserRole::Teacher, [
        'email' => 'review-teacher@example.com',
    ]);

    $testId = DB::table('tests')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'slug' => 'writing-mock',
        'title' => 'Writing Mock',
        'type' => 'skill_practice',
        'exam_type' => 'academic',
        'status' => 'published',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $moduleId = DB::table('test_modules')->insertGetId([
        'test_id' => $testId,
        'module' => 'writing',
        'title' => 'Writing',
        'sort_order' => 1,
        'status' => 'published',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $sectionId = DB::table('test_sections')->insertGetId([
        'test_module_id' => $moduleId,
        'title' => 'Task 2',
        'sort_order' => 1,
        'status' => 'published',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $bankId = DB::table('question_banks')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'slug' => 'writing-bank',
        'name' => 'Writing Bank',
        'module' => 'writing',
        'status' => 'published',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $questionId = DB::table('questions')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'question_bank_id' => $bankId,
        'module' => 'writing',
        'type' => 'essay',
        'prompt' => 'Discuss both views and give your opinion.',
        'status' => 'published',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $testQuestionId = DB::table('test_question')->insertGetId([
        'test_id' => $testId,
        'test_module_id' => $moduleId,
        'test_section_id' => $sectionId,
        'question_id' => $questionId,
        'sort_order' => 1,
        'marks' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $providerId = DB::table('ai_providers')->insertGetId([
        'name' => 'OpenAI',
        'slug' => 'openai',
        'driver' => 'openai',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $modelId = DB::table('ai_models')->insertGetId([
        'ai_provider_id' => $providerId,
        'name' => 'GPT-4o',
        'slug' => 'gpt-4o',
        'model_id' => 'gpt-4o',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $templateId = DB::table('ai_prompt_templates')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'name' => 'Writing Task 2 Scoring',
        'slug' => 'writing-task-2-scoring',
        'module' => 'writing',
        'task_type' => 'task_2',
        'system_prompt' => 'You are an IELTS examiner.',
        'user_prompt_template' => 'Score this essay: {{answer}}',
        'ai_model_id' => $modelId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $attemptId = DB::table('test_attempts')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'user_id' => $student->id,
        'test_id' => $testId,
        'test_module_id' => $moduleId,
        'current_section_id' => $sectionId,
        'status' => 'submitted',
        'started_at' => now()->subHour(),
        'submitted_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $answerId = DB::table('student_answers')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'test_attempt_id' => $attemptId,
        'test_section_id' => $sectionId,
        'question_id' => $questionId,
        'test_question_id' => $testQuestionId,
        'module' => 'writing',
        'answer_text' => 'This is my essay response.',
        'word_count' => 280,
        'is_final' => true,
        'submitted_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('autosave_logs')->insert([
        'test_attempt_id' => $attemptId,
        'student_answer_id' => $answerId,
        'payload' => json_encode(['answer_text' => 'Draft paragraph.']),
        'saved_at' => now()->subMinutes(10),
        'created_at' => now(),
    ]);

    $requestId = DB::table('ai_requests')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'student_answer_id' => $answerId,
        'test_attempt_id' => $attemptId,
        'ai_model_id' => $modelId,
        'ai_prompt_template_id' => $templateId,
        'status' => 'completed',
        'tokens_prompt' => 500,
        'tokens_completion' => 200,
        'dispatched_at' => now()->subMinutes(5),
        'completed_at' => now()->subMinutes(4),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $responseId = DB::table('ai_responses')->insertGetId([
        'ai_request_id' => $requestId,
        'response_text' => '{"band":6.5}',
        'response_json' => json_encode(['band' => 6.5]),
        'status' => 'completed',
        'latency_ms' => 1200,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $scoreId = DB::table('ai_evaluation_scores')->insertGetId([
        'ai_response_id' => $responseId,
        'student_answer_id' => $answerId,
        'overall_band' => 6.5,
        'criteria' => json_encode(['TA' => 6.5, 'CC' => 6.0, 'LR' => 7.0, 'GRA' => 6.5]),
        'requires_review' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('ai_evaluation_mistakes')->insert([
        'ai_evaluation_score_id' => $scoreId,
        'category' => 'grammar',
        'severity' => 'warning',
        'message' => 'Subject-verb agreement error in paragraph 2.',
        'suggestion' => 'Use plural verb with plural subject.',
        'sort_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $resultId = DB::table('results')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'test_attempt_id' => $attemptId,
        'overall_band' => 6.5,
        'raw_score' => 6.5,
        'max_score' => 9,
        'status' => 'final',
        'computed_at' => now(),
        'published_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('band_scores')->insert([
        'result_id' => $resultId,
        'module' => 'writing',
        'band' => 6.5,
        'raw_score' => 6.5,
        'max_score' => 9,
        'scoring_method' => 'ai',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('pdf_reports')->insert([
        'uuid' => (string) Str::uuid(),
        'result_id' => $resultId,
        'test_attempt_id' => $attemptId,
        'user_id' => $student->id,
        'report_type' => 'result_summary',
        'file_path' => 'reports/result-summary.pdf',
        'file_size_bytes' => 102400,
        'status' => 'completed',
        'generated_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('teacher_reviews')->insert([
        'student_answer_id' => $answerId,
        'ai_evaluation_score_id' => $scoreId,
        'teacher_id' => $teacher->id,
        'original_band' => 6.5,
        'adjusted_band' => 7.0,
        'feedback' => 'Strong ideas; minor grammar issues only.',
        'status' => 'completed',
        'reviewed_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('test_attempts')->where('id', $attemptId)->value('status'))->toBe('submitted')
        ->and(DB::table('student_answers')->where('test_attempt_id', $attemptId)->count())->toBe(1)
        ->and((float) DB::table('ai_evaluation_scores')->where('id', $scoreId)->value('overall_band'))->toBe(6.5)
        ->and(DB::table('band_scores')->where('result_id', $resultId)->count())->toBe(1)
        ->and(DB::table('pdf_reports')->where('test_attempt_id', $attemptId)->count())->toBe(1)
        ->and(DB::table('teacher_reviews')->where('teacher_id', $teacher->id)->count())->toBe(1);
});

it('enforces one result per test attempt', function (): void {
    $student = createUserWithRole(\App\Enums\Auth\UserRole::Student);

    $testId = DB::table('tests')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'slug' => 'duplicate-result-test',
        'title' => 'Duplicate Result Test',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $attemptId = DB::table('test_attempts')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'user_id' => $student->id,
        'test_id' => $testId,
        'status' => 'completed',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('results')->insert([
        'uuid' => (string) Str::uuid(),
        'test_attempt_id' => $attemptId,
        'status' => 'final',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('results')->insert([
        'uuid' => (string) Str::uuid(),
        'test_attempt_id' => $attemptId,
        'status' => 'final',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});
