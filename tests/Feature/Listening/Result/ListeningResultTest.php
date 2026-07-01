<?php

declare(strict_types=1);

use App\Actions\Listening\Evaluation\EvaluateListeningAttemptAction;
use App\Enums\Auth\UserRole;
use App\Enums\Commerce\IeltsModule;
use App\Enums\Listening\ListeningAnswerFormat;
use App\Enums\Listening\ListeningAttemptStatus;
use App\Enums\Listening\ListeningEvaluationStatus;
use App\Enums\Listening\ListeningLayoutType;
use App\Enums\Listening\ListeningQuestionType;
use App\Enums\Listening\ListeningResultStatus;
use App\Enums\Listening\ListeningTestStatus;
use App\Enums\Listening\ListeningTestType;
use App\Jobs\Listening\Result\BuildListeningResultJob;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningAttemptAnswer;
use App\Models\Listening\ListeningAttemptEvaluation;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningResult;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Models\Listening\ListeningTestSetting;
use App\Models\User;
use App\Services\Listening\Result\ListeningResultBuilderService;
use App\Services\Listening\Result\ListeningResultService;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    seedRbac();
    config([
        'listening.answer_engine.mode' => 'sync',
        'listening.answer_engine.evaluate_on_submit' => true,
        'listening.results.auto_build_after_evaluation' => true,
        'listening.results.show_accepted_answers_to_students' => false,
    ]);
});

function listeningResultAdmin(): User
{
    return createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);
}

function listeningResultStudent(): User
{
    $student = createUserWithRole(UserRole::Student, ['email_verified_at' => now()]);

    assignStudentPackage($student, createDemoPackage([
        'slug' => 'listening-result-package-'.uniqid(),
        'module_access' => [IeltsModule::Listening->value],
        'is_public' => true,
        'is_active' => true,
    ]));

    return $student;
}

function listeningResultOtherStudent(): User
{
    return createUserWithRole(UserRole::Student, ['email_verified_at' => now()]);
}

function listeningResultTest(): ListeningTest
{
    $admin = listeningResultAdmin();
    $test = ListeningTest::query()->create([
        'title' => 'Result Test '.uniqid(),
        'slug' => 'result-'.uniqid(),
        'test_code' => 'LST-RST-'.strtoupper(uniqid()),
        'test_type' => ListeningTestType::Academic,
        'status' => ListeningTestStatus::Published,
        'is_active' => true,
        'duration_minutes' => 30,
        'transfer_time_minutes' => 10,
        'total_sections' => 1,
        'total_questions' => 2,
        'published_at' => now(),
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    ListeningTestSetting::query()->create(array_merge(
        ListeningTestSetting::officialDefaults(),
        ['listening_test_id' => $test->id, 'show_correct_answer' => true],
    ));

    return $test;
}

function listeningResultSection(ListeningTest $test): ListeningSection
{
    return ListeningSection::query()->create([
        'listening_test_id' => $test->id,
        'section_number' => 1,
        'title' => 'Section 1',
        'start_question_number' => 1,
        'end_question_number' => 2,
        'total_questions' => 2,
        'is_active' => true,
    ]);
}

function listeningResultQuestion(ListeningTest $test, ListeningSection $section, int $number, string $correct): ListeningQuestion
{
    $group = ListeningQuestionGroup::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'start_question_number' => $number,
        'end_question_number' => $number,
        'title' => "Group {$number}",
        'question_type' => ListeningQuestionType::ShortAnswer,
        'total_questions' => 1,
        'display_order' => $number,
        'layout_type' => ListeningLayoutType::Default,
        'is_active' => true,
    ]);

    return ListeningQuestion::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'listening_question_group_id' => $group->id,
        'question_number' => $number,
        'question_type' => ListeningQuestionType::ShortAnswer,
        'question_text' => "Question {$number}",
        'correct_answer' => [['value' => $correct, 'type' => 'text']],
        'accepted_answers' => [['value' => $correct.'s', 'type' => 'text']],
        'answer_format' => ListeningAnswerFormat::Text,
        'marks' => 1,
        'is_active' => true,
        'display_order' => $number,
    ]);
}

function listeningResultAttempt(ListeningTest $test, User $student): ListeningAttempt
{
    return ListeningAttempt::query()->create([
        'user_id' => $student->id,
        'listening_test_id' => $test->id,
        'status' => ListeningAttemptStatus::Submitted,
        'started_at' => now()->subHour(),
        'submitted_at' => now(),
        'total_questions' => 2,
    ]);
}

function listeningResultAnswer(ListeningAttempt $attempt, ListeningQuestion $question, string $value): ListeningAttemptAnswer
{
    return ListeningAttemptAnswer::query()->create([
        'listening_attempt_id' => $attempt->id,
        'listening_test_id' => $attempt->listening_test_id,
        'listening_question_id' => $question->id,
        'question_number' => $question->question_number,
        'student_answer' => [['value' => $value, 'type' => 'text']],
    ]);
}

function listeningResultEvaluatedAttempt(): array
{
    $test = listeningResultTest();
    $section = listeningResultSection($test);
    $q1 = listeningResultQuestion($test, $section, 1, 'library');
    $q2 = listeningResultQuestion($test, $section, 2, 'museum');
    $student = listeningResultStudent();
    $attempt = listeningResultAttempt($test, $student);
    listeningResultAnswer($attempt, $q1, 'library');
    listeningResultAnswer($attempt, $q2, 'wrong');

    app(EvaluateListeningAttemptAction::class)->execute($attempt);

    $evaluation = ListeningAttemptEvaluation::query()
        ->where('listening_attempt_id', $attempt->id)
        ->latest('id')
        ->first();

    $result = app(ListeningResultService::class)->buildFromEvaluation($evaluation);

    return compact('test', 'student', 'attempt', 'evaluation', 'result', 'q1', 'q2');
}

it('builds listening result from completed evaluation', function (): void {
    ['result' => $result, 'evaluation' => $evaluation] = listeningResultEvaluatedAttempt();

    expect($result->status)->toBe(ListeningResultStatus::Ready)
        ->and($result->listening_attempt_evaluation_id)->toBe($evaluation->id)
        ->and((float) $result->raw_score)->toBe(1.0)
        ->and($result->result_code)->toStartWith('LST-')
        ->and($result->section_breakdown)->not->toBeEmpty()
        ->and($result->question_type_breakdown)->not->toBeEmpty()
        ->and($result->question_summary)->toHaveCount(2)
        ->and($result->result_snapshot)->toHaveKey('generated_at');
});

it('student can view own ready result', function (): void {
    ['student' => $student, 'result' => $result, 'attempt' => $attempt] = listeningResultEvaluatedAttempt();

    $response = $this->actingAs($student)->get(route('student.listening.attempts.result', $attempt));

    $response->assertOk()
        ->assertSee('Listening Test Results')
        ->assertSee('Band Score')
        ->assertSee('Question Review')
        ->assertSee('Back to Listening Tests');
});

it('student can view attempt result review page', function (): void {
    ['student' => $student, 'attempt' => $attempt] = listeningResultEvaluatedAttempt();

    $this->actingAs($student)
        ->get(route('student.listening.attempts.result.review', $attempt))
        ->assertOk()
        ->assertSee('Explanation Review')
        ->assertSee('library')
        ->assertSee('museum')
        ->assertSee('Back to Summary');
});

it('legacy result url redirects to attempt result page', function (): void {
    ['student' => $student, 'result' => $result, 'attempt' => $attempt] = listeningResultEvaluatedAttempt();

    $this->actingAs($student)
        ->get(route('student.listening.results.show', $result))
        ->assertRedirect(route('student.listening.attempts.result', $attempt));
});

it('student cannot view another students result', function (): void {
    ['result' => $result] = listeningResultEvaluatedAttempt();
    $other = listeningResultOtherStudent();

    $this->actingAs($other)->get(route('student.listening.results.show', $result))->assertForbidden();
});

it('student cannot view hidden result', function (): void {
    ['student' => $student, 'result' => $result] = listeningResultEvaluatedAttempt();

    app(ListeningResultService::class)->hide($result);

    $this->actingAs($student)->get(route('student.listening.results.show', $result->fresh()))->assertForbidden();
});

it('hides accepted answers from student by default', function (): void {
    ['student' => $student, 'attempt' => $attempt] = listeningResultEvaluatedAttempt();

    $this->actingAs($student)
        ->get(route('student.listening.attempts.result.review', $attempt))
        ->assertOk()
        ->assertDontSee('librarys');
});

it('admin can view full question summary', function (): void {
    ['result' => $result] = listeningResultEvaluatedAttempt();
    $admin = listeningResultAdmin();

    $response = $this->actingAs($admin)->get(route('admin.listening.results.show', $result));

    $response->assertOk()
        ->assertSee('Question Summary (Admin)')
        ->assertSee('library')
        ->assertSee('wrong');
});

it('admin can publish hide and rebuild result', function (): void {
    ['result' => $result] = listeningResultEvaluatedAttempt();
    $admin = listeningResultAdmin();

    $this->actingAs($admin)->post(route('admin.listening.results.hide', $result))->assertRedirect();
    expect($result->fresh()->status)->toBe(ListeningResultStatus::Hidden);

    $this->actingAs($admin)->post(route('admin.listening.results.publish', $result))->assertRedirect();
    expect($result->fresh()->is_visible_to_student)->toBeTrue();

    $this->actingAs($admin)->post(route('admin.listening.results.rebuild', $result))->assertRedirect();
    expect($result->fresh()->status)->toBe(ListeningResultStatus::Ready);
});

it('shows pending page when result is pending', function (): void {
    $test = listeningResultTest();
    $student = listeningResultStudent();
    $attempt = listeningResultAttempt($test, $student);

    ListeningResult::query()->create([
        'listening_attempt_id' => $attempt->id,
        'listening_test_id' => $test->id,
        'user_id' => $student->id,
        'status' => ListeningResultStatus::Pending->value,
        'submitted_at' => now(),
        'is_visible_to_student' => true,
        'total_questions' => 2,
    ]);

    $this->actingAs($student)
        ->get(route('student.listening.attempts.result', $attempt))
        ->assertOk()
        ->assertSee('being prepared');
});

it('shows failed page for student', function (): void {
    $test = listeningResultTest();
    $student = listeningResultStudent();
    $attempt = listeningResultAttempt($test, $student);

    ListeningResult::query()->create([
        'listening_attempt_id' => $attempt->id,
        'listening_test_id' => $test->id,
        'user_id' => $student->id,
        'status' => ListeningResultStatus::Failed->value,
        'submitted_at' => now(),
        'is_visible_to_student' => true,
        'total_questions' => 2,
        'meta' => ['failure_reason' => 'Engine timeout'],
    ]);

    $this->actingAs($student)
        ->get(route('student.listening.attempts.result', $attempt))
        ->assertOk()
        ->assertSee('could not be prepared');
});

it('dispatches build job after evaluation when configured', function (): void {
    Queue::fake();
    config(['listening.answer_engine.mode' => 'sync', 'listening.results.auto_build_after_evaluation' => true]);

    $test = listeningResultTest();
    $section = listeningResultSection($test);
    $q1 = listeningResultQuestion($test, $section, 1, 'alpha');
    $attempt = listeningResultAttempt($test, listeningResultStudent());
    listeningResultAnswer($attempt, $q1, 'alpha');

    app(EvaluateListeningAttemptAction::class)->execute($attempt);

    Queue::assertPushed(BuildListeningResultJob::class);
});

it('does not re-evaluate on result page load', function (): void {
    ['student' => $student, 'result' => $result, 'evaluation' => $evaluation, 'attempt' => $attempt] = listeningResultEvaluatedAttempt();

    $evaluation->update(['raw_score' => 99]);
    $storedScore = $result->raw_score;

    $this->actingAs($student)->get(route('student.listening.attempts.result', $attempt))->assertOk();

    expect((float) $result->fresh()->raw_score)->toBe((float) $storedScore);
});
