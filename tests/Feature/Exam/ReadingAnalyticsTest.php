<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Commerce\IeltsModule;
use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\ReadingQuestionType;
use App\Enums\Exam\TestAttemptStatus;
use App\Models\ExamTest;
use App\Models\Question;
use App\Models\ReadingAnalytics;
use App\Models\ReadingQuestionTiming;
use App\Models\StudentAnswer;
use App\Models\TestAttempt;
use App\Services\Admin\Exam\ReadingTestBuilderService;
use App\Services\Exam\Analytics\ReadingAnalyticsReportService;
use App\Services\Exam\Analytics\ReadingQuestionTimingService;
use App\Services\Exam\Scoring\ReadingScoringEngine;

beforeEach(function (): void {
    seedRbac();
});

function createAnalyticsReadingTest(): ExamTest
{
    $admin = createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);

    $test = app(ReadingTestBuilderService::class)->importTest([
        'test' => [
            'title' => 'Analytics Reading Test',
            'slug' => 'analytics-reading-'.uniqid(),
            'exam_type' => ExamType::Academic->value,
            'status' => PublishStatus::Published->value,
        ],
        'passages' => [[
            'title' => 'Analytics Passage',
            'sort_order' => 1,
            'stimulus_text' => 'Analytics passage text.',
            'questions' => [
                [
                    'type' => ReadingQuestionType::TrueFalseNg->value,
                    'question_number' => 1,
                    'prompt' => 'Statement A',
                    'correct_answer' => 'True',
                    'marks' => 1,
                    'sort_order' => 1,
                ],
                [
                    'type' => ReadingQuestionType::ShortAnswer->value,
                    'question_number' => 2,
                    'prompt' => 'Keyword?',
                    'correct_answer' => 'analytics',
                    'marks' => 1,
                    'sort_order' => 2,
                ],
                [
                    'type' => ReadingQuestionType::MultipleChoiceSingle->value,
                    'question_number' => 3,
                    'prompt' => 'Pick one',
                    'options' => ['Red', 'Blue', 'Green'],
                    'correct_answer' => 'Blue',
                    'marks' => 1,
                    'sort_order' => 3,
                ],
            ],
        ]],
    ], $admin);

    $test->update(['status' => PublishStatus::Published, 'published_at' => now()]);

    return $test->fresh();
}

function scoreAnalyticsAttempt(ExamTest $test, array $answersByNumber, array $timingsByNumber = []): ReadingAnalytics
{
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'analytics-student-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    assignStudentPackage($student, createDemoPackage([
        'slug' => 'analytics-package-'.uniqid(),
        'module_access' => [IeltsModule::Reading->value],
        'is_public' => true,
        'is_active' => true,
    ]));

    $module = app(ReadingTestBuilderService::class)->readingModule($test);
    $section = $module->sections()->firstOrFail();

    $attempt = TestAttempt::query()->create([
        'user_id' => $student->id,
        'test_id' => $test->id,
        'test_module_id' => $module->id,
        'current_section_id' => $section->id,
        'status' => TestAttemptStatus::InProgress,
        'started_at' => now(),
        'time_remaining_seconds' => 3600,
    ]);

    $questions = Question::query()
        ->whereIn('id', $test->testQuestions()->pluck('question_id'))
        ->get()
        ->keyBy('question_number');

    foreach ($answersByNumber as $number => $answerText) {
        $question = $questions->get($number);
        if ($question === null) {
            continue;
        }

        StudentAnswer::query()->create([
            'test_attempt_id' => $attempt->id,
            'test_section_id' => $section->id,
            'question_id' => $question->id,
            'module' => IeltsModule::Reading->value,
            'answer_text' => $answerText,
        ]);
    }

    foreach ($timingsByNumber as $number => $seconds) {
        $question = $questions->get($number);
        if ($question === null) {
            continue;
        }

        ReadingQuestionTiming::query()->create([
            'test_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'time_spent_seconds' => $seconds,
            'visit_count' => 1,
            'first_viewed_at' => now(),
            'last_viewed_at' => now(),
        ]);
    }

    app(ReadingScoringEngine::class)->scoreAttempt($attempt);

    return ReadingAnalytics::query()->where('test_attempt_id', $attempt->id)->firstOrFail();
}

it('creates reading analytics tables', function (): void {
    expect(\Illuminate\Support\Facades\Schema::hasTable('reading_question_timings'))->toBeTrue();
    expect(\Illuminate\Support\Facades\Schema::hasTable('reading_analytics'))->toBeTrue();
});

it('tracks time per question through timing service', function (): void {
    $test = createAnalyticsReadingTest();
    $analytics = scoreAnalyticsAttempt($test, [1 => 'True'], [1 => 45, 2 => 90]);

    $timings = app(ReadingQuestionTimingService::class)->timingsForAttempt($analytics->attempt);

    expect(collect($timings)->firstWhere('question_number', 1)['time_spent_seconds'])->toBe(45);
    expect(collect($timings)->firstWhere('question_number', 2)['time_spent_seconds'])->toBe(90);
});

it('builds attempt analytics with accuracy skipped average time and heat map', function (): void {
    $analytics = scoreAnalyticsAttempt(
        createAnalyticsReadingTest(),
        [1 => 'True', 2 => '', 3 => 'Blue'],
        [1 => 30, 2 => 10, 3 => 75]
    );

    expect((float) $analytics->accuracy_percent)->toBe(66.67);
    expect($analytics->skipped_count)->toBe(1);
    expect($analytics->average_time_seconds)->toBe(38);
    expect($analytics->time_per_question)->toHaveCount(3);
    expect($analytics->heat_map['cells'])->toHaveCount(3);
    expect(collect($analytics->time_per_question)->firstWhere('question_number', 2)['is_skipped'])->toBeTrue();
});

it('generates band distribution for admin test summary', function (): void {
    $test = createAnalyticsReadingTest();

    scoreAnalyticsAttempt($test, [1 => 'True', 2 => 'analytics', 3 => 'Blue'], [1 => 20, 2 => 20, 3 => 20]);
    scoreAnalyticsAttempt($test, [1 => 'False', 2 => 'wrong', 3 => 'Red'], [1 => 40, 2 => 40, 3 => 40]);

    $summary = app(ReadingAnalyticsReportService::class)->testSummary($test);

    expect($summary['attempt_count'])->toBe(2);
    expect($summary['band_distribution'])->not->toBeEmpty();
    expect($summary['heat_map']['cells'])->not->toBeEmpty();
    expect($summary['average_accuracy'])->toBeGreaterThan(0);
});

it('allows admin to view analytics pages and export csv report', function (): void {
    $test = createAnalyticsReadingTest();
    $analytics = scoreAnalyticsAttempt($test, [1 => 'True', 2 => 'analytics', 3 => 'Blue']);

    $admin = createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);

    $this->actingAs($admin)
        ->get(route('admin.reading-analytics.index'))
        ->assertOk()
        ->assertSee('Reading Analytics');

    $this->actingAs($admin)
        ->get(route('admin.reading-analytics.show', $test))
        ->assertOk()
        ->assertSee('Accuracy Heat Map');

    $this->actingAs($admin)
        ->get(route('admin.reading-analytics.attempt', $analytics))
        ->assertOk()
        ->assertSee('Time Per Question');

    $response = $this->actingAs($admin)->get(route('admin.reading-analytics.export', $test));
    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('.csv');
});

it('stores analytics automatically when scoring engine completes', function (): void {
    $test = createAnalyticsReadingTest();
    $analytics = scoreAnalyticsAttempt($test, [1 => 'True', 2 => 'analytics', 3 => 'Blue']);

    expect($analytics->result_id)->not->toBeNull();
    expect($analytics->band)->not->toBeNull();
    expect($analytics->computed_at)->not->toBeNull();
});

it('syncs question timings from player autosave payload', function (): void {
    $test = createAnalyticsReadingTest();
    $student = createUserWithRole(UserRole::Student, ['email_verified_at' => now()]);

    assignStudentPackage($student, createDemoPackage([
        'module_access' => [IeltsModule::Reading->value],
        'is_public' => true,
        'is_active' => true,
    ]));

    $module = app(ReadingTestBuilderService::class)->readingModule($test);
    $attempt = TestAttempt::query()->create([
        'user_id' => $student->id,
        'test_id' => $test->id,
        'test_module_id' => $module->id,
        'status' => TestAttemptStatus::InProgress,
        'started_at' => now(),
    ]);

    $question = Question::query()->where('question_number', 1)->firstOrFail();

    $this->actingAs($student)->putJson(route('exam.reading.autosave', $attempt), [
        'question_timings' => [[
            'question_id' => $question->id,
            'time_spent_seconds' => 55,
            'visit_count' => 2,
        ]],
        'answers' => [],
    ])->assertOk();

    expect(ReadingQuestionTiming::query()->where('test_attempt_id', $attempt->id)->value('time_spent_seconds'))->toBe(55);
});

it('builds aggregate heat map accuracy across attempts', function (): void {
    $test = createAnalyticsReadingTest();
    scoreAnalyticsAttempt($test, [1 => 'True', 2 => 'analytics', 3 => 'Blue']);
    scoreAnalyticsAttempt($test, [1 => 'False', 2 => 'analytics', 3 => 'Blue']);

    $summary = app(ReadingAnalyticsReportService::class)->testSummary($test);
    $q1 = collect($summary['heat_map']['cells'])->firstWhere('question_number', 1);

    expect($q1['accuracy_percent'])->toBe(50.0);
    expect($q1['attempt_count'])->toBe(2);
});
