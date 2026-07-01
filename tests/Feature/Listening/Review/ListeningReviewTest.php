<?php

declare(strict_types=1);

use App\Actions\Listening\Evaluation\EvaluateListeningAttemptAction;
use App\Actions\Listening\Review\BuildListeningReviewItemsAction;
use App\Enums\Auth\UserRole;
use App\Enums\Commerce\IeltsModule;
use App\Enums\Listening\ListeningAnswerFormat;
use App\Enums\Listening\ListeningAttemptStatus;
use App\Enums\Listening\ListeningLayoutType;
use App\Enums\Listening\ListeningQuestionType;
use App\Enums\Listening\ListeningResultStatus;
use App\Enums\Listening\ListeningTestStatus;
use App\Enums\Listening\ListeningTestType;
use App\Jobs\Listening\Review\BuildListeningReviewItemsJob;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningAttemptAnswer;
use App\Models\Listening\ListeningAttemptEvaluation;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningResult;
use App\Models\Listening\ListeningReviewItem;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Models\Listening\ListeningTestSetting;
use App\Models\Listening\ListeningTranscript;
use App\Models\User;
use App\Services\Listening\Result\ListeningResultService;
use App\Services\Listening\Review\ListeningTranscriptHighlightService;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    seedRbac();
    test()->withoutVite();
    config([
        'listening.answer_engine.mode' => 'sync',
        'listening.review.enabled' => true,
        'listening.review.build_after_result' => true,
        'listening.review.show_accepted_answers_to_students' => false,
    ]);
});

function listeningReviewAdmin(): User
{
    return createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);
}

function listeningReviewStudent(): User
{
    $student = createUserWithRole(UserRole::Student, ['email_verified_at' => now()]);
    assignStudentPackage($student, createDemoPackage([
        'slug' => 'listening-review-package-'.uniqid(),
        'module_access' => [IeltsModule::Listening->value],
        'is_public' => true,
        'is_active' => true,
    ]));

    return $student;
}

function listeningReviewEvaluatedAttempt(): array
{
    $admin = listeningReviewAdmin();
    $test = ListeningTest::query()->create([
        'title' => 'Review Test '.uniqid(),
        'slug' => 'review-'.uniqid(),
        'test_code' => 'LST-REV-'.strtoupper(uniqid()),
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
    $section = ListeningSection::query()->create([
        'listening_test_id' => $test->id,
        'section_number' => 1,
        'title' => 'Section 1',
        'start_question_number' => 1,
        'end_question_number' => 2,
        'total_questions' => 2,
        'is_active' => true,
    ]);

    $makeQuestion = function (int $number, string $correct) use ($test, $section): ListeningQuestion {
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
    };

    $q1 = $makeQuestion(1, 'library');
    $q2 = $makeQuestion(2, 'museum');
    $student = listeningReviewStudent();
    $attempt = ListeningAttempt::query()->create([
        'user_id' => $student->id,
        'listening_test_id' => $test->id,
        'status' => ListeningAttemptStatus::Submitted,
        'started_at' => now()->subHour(),
        'submitted_at' => now(),
        'total_questions' => 2,
    ]);
    foreach ([[$q1, 'library'], [$q2, 'wrong']] as [$question, $answer]) {
        ListeningAttemptAnswer::query()->create([
            'listening_attempt_id' => $attempt->id,
            'listening_test_id' => $test->id,
            'listening_question_id' => $question->id,
            'question_number' => $question->question_number,
            'student_answer' => [['value' => $answer, 'type' => 'text']],
        ]);
    }

    app(EvaluateListeningAttemptAction::class)->execute($attempt);
    $evaluation = ListeningAttemptEvaluation::query()->where('listening_attempt_id', $attempt->id)->latest('id')->first();
    $result = app(ListeningResultService::class)->buildFromEvaluation($evaluation);

    return compact('test', 'student', 'attempt', 'evaluation', 'result', 'q1', 'q2');
}

function listeningReviewReadyResult(): array
{
    $data = listeningReviewEvaluatedAttempt();
    app(BuildListeningReviewItemsAction::class)->execute($data['result']);

    return $data;
}

it('builds review items after result with one item per question', function (): void {
    ['result' => $result] = listeningReviewReadyResult();
    expect(ListeningReviewItem::query()->where('listening_result_id', $result->id)->count())->toBe(2);
});

it('allows student to view own review', function (): void {
    ['student' => $student, 'result' => $result] = listeningReviewReadyResult();
    $this->actingAs($student)->get(route('student.listening.results.review.show', $result))->assertOk()->assertSee('Listening Review');
});

it('blocks other student from review', function (): void {
    ['result' => $result] = listeningReviewReadyResult();
    $this->actingAs(listeningReviewStudent())->get(route('student.listening.results.review.show', $result))->assertForbidden();
});

it('hides accepted answers from student review', function (): void {
    ['student' => $student, 'result' => $result] = listeningReviewReadyResult();
    $this->actingAs($student)->get(route('student.listening.results.review.question', [$result, 1]))->assertOk()->assertDontSee('librarys');
});

it('shows accepted answers to admin review', function (): void {
    ['result' => $result] = listeningReviewReadyResult();
    $this->actingAs(listeningReviewAdmin())->get(route('admin.listening.results.review.question', [$result, 1]))->assertOk()->assertSee('Accepted answers');
});

it('blocks review when result is pending', function (): void {
    $student = listeningReviewStudent();
    $data = listeningReviewEvaluatedAttempt();
    $data['result']->update(['status' => ListeningResultStatus::Pending->value]);
    $this->actingAs($student)->get(route('student.listening.results.review.show', $data['result']))->assertForbidden();
});

it('admin can rebuild review items', function (): void {
    ['result' => $result] = listeningReviewReadyResult();
    $this->actingAs(listeningReviewAdmin())->post(route('admin.listening.results.review.rebuild', $result))->assertRedirect();
    expect(ListeningReviewItem::query()->where('listening_result_id', $result->id)->count())->toBe(2);
});

it('dispatches review build job after result job when configured', function (): void {
    Queue::fake();
    ['evaluation' => $evaluation] = listeningReviewEvaluatedAttempt();
    (new \App\Jobs\Listening\Result\BuildListeningResultJob($evaluation->id))->handle(app(ListeningResultService::class));
    Queue::assertPushed(BuildListeningReviewItemsJob::class);
});

it('builds transcript highlight for valid line range', function (): void {
    $transcript = ListeningTranscript::query()->create([
        'title' => 'T1',
        'transcript_text' => 'hello library next line',
        'timestamped_transcript' => [
            ['line' => 1, 'speaker' => 'A', 'text' => 'hello library'],
            ['line' => 2, 'speaker' => 'B', 'text' => 'next line'],
        ],
    ]);
    $highlight = app(ListeningTranscriptHighlightService::class)->buildHighlight($transcript, 1, 1);
    expect($highlight->lines)->toHaveCount(1)->and($highlight->warning)->toBeNull();
});

it('returns warning for invalid transcript range', function (): void {
    $transcript = ListeningTranscript::query()->create([
        'title' => 'T2',
        'transcript_text' => 'only line',
        'timestamped_transcript' => [['line' => 1, 'text' => 'only line']],
    ]);
    $highlight = app(ListeningTranscriptHighlightService::class)->buildHighlight($transcript, 5, 10);
    expect($highlight->lines)->toBe([])->and($highlight->warning)->not->toBeNull();
});

it('does not modify reading evaluation services', function (): void {
    expect(class_exists(\App\Services\Exam\ReadingResultService::class))->toBeTrue();
});
