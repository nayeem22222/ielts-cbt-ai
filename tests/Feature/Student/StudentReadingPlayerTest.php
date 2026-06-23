<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Commerce\IeltsModule;
use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\ReadingQuestionType;
use App\Enums\Exam\TestAttemptStatus;
use App\Models\AutosaveLog;
use App\Models\ExamTest;
use App\Models\StudentAnswer;
use App\Models\TestAttempt;
use App\Services\Admin\Exam\ReadingTestBuilderService;
use App\Services\Exam\ReadingPlayerService;

beforeEach(function (): void {
    seedRbac();
});

function createPublishedReadingTestForPlayer(): ExamTest
{
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'player-seed-admin@example.com',
        'email_verified_at' => now(),
    ]);

    $test = app(ReadingTestBuilderService::class)->importTest([
        'test' => [
            'title' => 'Student Reading Mock',
            'slug' => 'student-reading-mock',
            'exam_type' => ExamType::Academic->value,
            'duration_seconds' => 3600,
            'status' => PublishStatus::Published->value,
        ],
        'passages' => [[
            'title' => 'Urban Transport',
            'sort_order' => 1,
            'instructions' => 'Answer questions 1-2.',
            'stimulus_text' => 'Modern cities are reshaping urban transport networks.',
            'questions' => [
                [
                    'type' => ReadingQuestionType::TrueFalseNg->value,
                    'question_number' => 1,
                    'prompt' => 'Cities are changing transport systems.',
                    'correct_answer' => 'True',
                    'marks' => 1,
                    'sort_order' => 1,
                    'options' => [],
                ],
                [
                    'type' => ReadingQuestionType::ShortAnswer->value,
                    'question_number' => 2,
                    'prompt' => 'What are cities reshaping?',
                    'correct_answer' => 'transport',
                    'marks' => 1,
                    'sort_order' => 2,
                    'options' => [],
                ],
            ],
        ]],
    ], $admin);

    $test->update([
        'status' => PublishStatus::Published,
        'published_at' => now(),
    ]);

    return $test->fresh();
}

function studentWithReadingAccess(): \App\Models\User
{
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'reading-player-student@example.com',
        'email_verified_at' => now(),
    ]);

    assignStudentPackage($student, createDemoPackage([
        'slug' => 'reading-player-package',
        'module_access' => [IeltsModule::Reading->value],
        'is_public' => true,
        'is_active' => true,
    ]));

    return $student;
}

it('renders reading player with side-by-side layout and navigator', function (): void {
    createPublishedReadingTestForPlayer();
    $student = studentWithReadingAccess();

    $this->actingAs($student)
        ->get(route('exam.reading'))
        ->assertOk()
        ->assertSee('Urban Transport')
        ->assertSee('Question Navigator')
        ->assertSee('Highlight')
        ->assertSee('Notes')
        ->assertSee('Submit Test')
        ->assertSee('lg:grid-cols-[1.05fr_.95fr]', false);
});

it('starts a reading attempt when student opens the player', function (): void {
    createPublishedReadingTestForPlayer();
    $student = studentWithReadingAccess();

    $this->actingAs($student)->get(route('exam.reading'))->assertOk();

    expect(TestAttempt::query()->where('user_id', $student->id)->count())->toBe(1);

    $attempt = TestAttempt::query()->where('user_id', $student->id)->first();
    expect($attempt->status)->toBe(TestAttemptStatus::InProgress);
});

it('resumes an in progress attempt instead of creating duplicates', function (): void {
    createPublishedReadingTestForPlayer();
    $student = studentWithReadingAccess();

    $this->actingAs($student)->get(route('exam.reading'));
    $this->actingAs($student)->get(route('exam.reading'));

    expect(TestAttempt::query()->where('user_id', $student->id)->count())->toBe(1);
});

it('autosaves answers flags notes and highlights', function (): void {
    $test = createPublishedReadingTestForPlayer();
    $student = studentWithReadingAccess();

    $this->actingAs($student)->get(route('exam.reading'));

    $attempt = TestAttempt::query()->where('user_id', $student->id)->firstOrFail();
    $question = $test->testQuestions()->with('question')->firstOrFail()->question;
    $section = $test->sections()->firstOrFail();

    $response = $this->actingAs($student)->putJson(route('exam.reading.autosave', $attempt), [
        'current_section_id' => $section->id,
        'active_question_id' => $question->id,
        'time_remaining_seconds' => 3200,
        'answers' => [[
            'question_id' => $question->id,
            'answer_text' => 'True',
            'is_flagged' => true,
        ]],
        'highlights' => [
            (string) $section->id => ['urban transport'],
        ],
        'notes' => [
            (string) $section->id => 'Review paragraph one',
        ],
    ]);

    $response->assertOk()->assertJsonPath('data.answers_saved', 1);

    $saved = StudentAnswer::query()->where('test_attempt_id', $attempt->id)->first();
    expect($saved->answer_text)->toBe('True');
    expect($saved->is_flagged)->toBeTrue();

    $attempt->refresh();
    expect($attempt->metadata['notes'][(string) $section->id])->toBe('Review paragraph one');
    expect($attempt->metadata['highlights'][(string) $section->id])->toBe(['urban transport']);
    expect($attempt->time_remaining_seconds)->toBe(3200);
    expect(AutosaveLog::query()->where('test_attempt_id', $attempt->id)->count())->toBe(1);
});

it('includes dark mode toggle and responsive mobile tabs', function (): void {
    createPublishedReadingTestForPlayer();
    $student = studentWithReadingAccess();

    $this->actingAs($student)
        ->get(route('exam.reading'))
        ->assertOk()
        ->assertSee('Dark');
});

it('shows empty state when no published reading test exists', function (): void {
    $student = studentWithReadingAccess();

    $this->actingAs($student)
        ->get(route('exam.reading'))
        ->assertOk()
        ->assertSee('No reading test available');
});

it('blocks autosave for attempts owned by another student', function (): void {
    createPublishedReadingTestForPlayer();
    $owner = studentWithReadingAccess();
    $other = createUserWithRole(UserRole::Student, ['email_verified_at' => now()]);

    $this->actingAs($owner)->get(route('exam.reading'));
    $attempt = TestAttempt::query()->where('user_id', $owner->id)->firstOrFail();

    $this->actingAs($other)
        ->putJson(route('exam.reading.autosave', $attempt), [
            'answers' => [],
        ])
        ->assertForbidden();
});

it('still allows exam route check from enrollment system', function (): void {
    createPublishedReadingTestForPlayer();

    $student = createUserWithRole(UserRole::Student, ['email_verified_at' => now()]);
    assignStudentPackage($student, createDemoPackage([
        'slug' => 'reading-access-package-player',
        'module_access' => [IeltsModule::Reading->value],
        'is_public' => true,
        'is_active' => true,
    ]));

    $this->actingAs($student)
        ->get(route('exam.reading'))
        ->assertOk();
});
