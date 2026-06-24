<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Commerce\IeltsModule;
use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\OfficialReadingQuestionType;
use App\Enums\Exam\PassageStatus;
use App\Enums\Exam\TestAttemptStatus;
use App\Models\ReadingAttempt;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingTest;
use App\Models\User;
use App\Services\Exam\ReadingTimerService;
use Illuminate\Support\Carbon;

beforeEach(function (): void {
    seedRbac();
    test()->withoutVite();
});

function timerReviewStudent(): User
{
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'reading-timer-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    assignStudentPackage($student, createDemoPackage([
        'slug' => 'reading-timer-package-'.uniqid(),
        'module_access' => [IeltsModule::Reading->value],
        'is_public' => true,
        'is_active' => true,
    ]));

    return $student;
}

function createTimerReviewTest(): ReadingTest
{
    $admin = createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);

    $test = ReadingTest::query()->create([
        'title' => 'Timer Review Test',
        'slug' => 'timer-review-'.uniqid(),
        'exam_type' => ExamType::Academic,
        'duration_minutes' => 60,
        'status' => PublishStatus::Published,
        'published_at' => now(),
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $passage = ReadingPassage::query()->create([
        'reading_test_id' => $test->id,
        'part_number' => 1,
        'title' => 'Passage',
        'start_question' => 1,
        'end_question' => 3,
        'content_html' => '<p>Body</p>',
        'status' => PassageStatus::Published,
        'sort_order' => 1,
    ]);

    $group = ReadingQuestionGroup::query()->create([
        'passage_id' => $passage->id,
        'title' => 'Q1-3',
        'question_type' => OfficialReadingQuestionType::TrueFalseNotGiven,
        'start_question' => 1,
        'end_question' => 3,
        'sort_order' => 1,
        'status' => PassageStatus::Published,
    ]);

    foreach ([1, 2, 3] as $number) {
        $group->questions()->create([
            'question_number' => $number,
            'prompt' => "Statement {$number}",
            'marks' => 1,
            'sort_order' => $number,
        ]);
    }

    return $test->fresh();
}

function startTimerAttempt(User $student, ReadingTest $test): ReadingAttempt
{
    test()->actingAs($student)->get(route('reading-tests.start', $test))->assertOk();

    return ReadingAttempt::query()
        ->where('user_id', $student->id)
        ->where('reading_test_id', $test->id)
        ->where('status', TestAttemptStatus::InProgress)
        ->firstOrFail();
}

it('persists timer from server started_at and does not reset on refresh', function (): void {
    Carbon::setTestNow('2026-06-24 10:00:00');

    $student = timerReviewStudent();
    $test = createTimerReviewTest();
    $attempt = startTimerAttempt($student, $test);

    expect($attempt->started_at?->toDateTimeString())->toBe('2026-06-24 10:00:00');
    expect($attempt->remaining_seconds)->toBe(3600);

    Carbon::setTestNow('2026-06-24 10:00:20');

    $this->actingAs($student)
        ->getJson(route('reading-attempts.timer', $attempt))
        ->assertOk()
        ->assertJsonPath('data.remaining_seconds', 3580);

    $this->actingAs($student)
        ->get(route('reading-tests.start', $test))
        ->assertOk()
        ->assertSee('timerLabel', false);

    $attempt->refresh();
    expect($attempt->remaining_seconds)->toBe(3580);

    Carbon::setTestNow();
});

it('returns review summary with answered unanswered and flagged counts', function (): void {
    $student = timerReviewStudent();
    $test = createTimerReviewTest();
    $attempt = startTimerAttempt($student, $test);
    $question = $test->questions()->where('question_number', 1)->firstOrFail();

    $this->actingAs($student)->postJson(route('reading-attempts.answers.store', $attempt), [
        'question_id' => $question->id,
        'question_number' => 1,
        'question_type' => OfficialReadingQuestionType::TrueFalseNotGiven->value,
        'passage_id' => $question->group->passage_id,
        'group_id' => $question->group_id,
        'answer' => 'TRUE',
    ])->assertOk();

    $flagQuestion = $test->questions()->where('question_number', 2)->firstOrFail();
    $this->actingAs($student)->postJson(route('reading-attempts.answers.flag', [$attempt, $flagQuestion]), [
        'flagged' => true,
    ])->assertOk();

    $this->actingAs($student)
        ->getJson(route('reading-attempts.review', $attempt))
        ->assertOk()
        ->assertJsonPath('data.summary.total', 3)
        ->assertJsonPath('data.summary.answered', 1)
        ->assertJsonPath('data.summary.unanswered', 2)
        ->assertJsonPath('data.summary.flagged', 1);
});

it('marks questions as visited and stores metadata', function (): void {
    $student = timerReviewStudent();
    $test = createTimerReviewTest();
    $attempt = startTimerAttempt($student, $test);
    $question = $test->questions()->where('question_number', 2)->firstOrFail();

    $this->actingAs($student)->postJson(route('reading-attempts.visited', $attempt), [
        'question_id' => $question->id,
    ])->assertOk()
        ->assertJsonPath('data.visited_questions', [2]);

    expect($attempt->fresh()->metadata['visited_questions'] ?? [])->toBe([2]);
});

it('submits attempt manually and blocks further saves', function (): void {
    $student = timerReviewStudent();
    $test = createTimerReviewTest();
    $attempt = startTimerAttempt($student, $test);
    $question = $test->questions()->firstOrFail();

    $this->actingAs($student)
        ->postJson(route('reading-attempts.submit', $attempt))
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');

    $attempt->refresh();
    expect($attempt->status)->toBe(TestAttemptStatus::Completed);
    expect($attempt->submitted_at)->not->toBeNull();

    $this->actingAs($student)->postJson(route('reading-attempts.answers.store', $attempt), [
        'question_id' => $question->id,
        'question_number' => $question->question_number,
        'question_type' => OfficialReadingQuestionType::TrueFalseNotGiven->value,
        'passage_id' => $question->group->passage_id,
        'group_id' => $question->group_id,
        'answer' => 'FALSE',
    ])->assertForbidden();
});

it('auto submits expired attempts', function (): void {
    Carbon::setTestNow('2026-06-24 10:00:00');

    $student = timerReviewStudent();
    $test = createTimerReviewTest();
    $attempt = startTimerAttempt($student, $test);

    Carbon::setTestNow('2026-06-24 11:01:00');

    expect(app(ReadingTimerService::class)->isExpired($attempt))->toBeTrue();

    $this->actingAs($student)
        ->postJson(route('reading-attempts.auto-submit', $attempt))
        ->assertOk()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.auto', true);

    expect($attempt->fresh()->status)->toBe(TestAttemptStatus::Completed);

    Carbon::setTestNow();
});

it('shows submitted placeholder page after submit', function (): void {
    $student = timerReviewStudent();
    $test = createTimerReviewTest();
    $attempt = startTimerAttempt($student, $test);

    $attempt->update([
        'status' => TestAttemptStatus::Submitted,
        'submitted_at' => now(),
    ]);

    $this->actingAs($student)
        ->get(route('reading-attempts.submitted', $attempt))
        ->assertOk()
        ->assertSee('Reading Test Submitted');
});
