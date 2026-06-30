<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Commerce\IeltsModule;
use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\OfficialReadingQuestionType;
use App\Enums\Exam\PassageStatus;
use App\Enums\Exam\TestAttemptStatus;
use App\Models\ReadingAnswer;
use App\Models\ReadingAttempt;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingTest;
use App\Models\User;

beforeEach(function (): void {
    seedRbac();
    test()->withoutVite();
});

function autosaveStudent(): User
{
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'reading-autosave-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    assignStudentPackage($student, createDemoPackage([
        'slug' => 'reading-autosave-package-'.uniqid(),
        'module_access' => [IeltsModule::Reading->value],
        'is_public' => true,
        'is_active' => true,
    ]));

    return $student;
}

function createAutosaveReadingTest(string $slug = 'autosave-reading-test'): ReadingTest
{
    $admin = createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);

    $test = ReadingTest::query()->create([
        'title' => 'Autosave Test',
        'slug' => $slug,
        'exam_type' => ExamType::Academic,
        'duration_minutes' => 60,
        'status' => PublishStatus::Published,
        'published_at' => now(),
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $passageOne = ReadingPassage::query()->create([
        'reading_test_id' => $test->id,
        'part_number' => 1,
        'title' => 'Passage One',
        'start_question' => 1,
        'end_question' => 2,
        'content_html' => '<p>Passage one content</p>',
        'status' => PassageStatus::Published,
        'sort_order' => 1,
    ]);

    $passageTwo = ReadingPassage::query()->create([
        'reading_test_id' => $test->id,
        'part_number' => 2,
        'title' => 'Passage Two',
        'start_question' => 3,
        'end_question' => 4,
        'content_html' => '<p>Passage two content</p>',
        'status' => PassageStatus::Published,
        'sort_order' => 2,
    ]);

    foreach ([
        [$passageOne, OfficialReadingQuestionType::TrueFalseNotGiven, 1, 2],
        [$passageTwo, OfficialReadingQuestionType::ShortAnswer, 3, 4],
    ] as [$passage, $type, $start, $end]) {
        $group = ReadingQuestionGroup::query()->create([
            'passage_id' => $passage->id,
            'title' => "Questions {$start}-{$end}",
            'question_type' => $type,
            'start_question' => $start,
            'end_question' => $end,
            'sort_order' => 1,
            'status' => PassageStatus::Published,
        ]);

        for ($n = $start; $n <= $end; $n++) {
            $group->questions()->create([
                'question_number' => $n,
                'prompt' => "Question {$n} prompt",
                'marks' => 1,
                'sort_order' => $n,
            ]);
        }
    }

    return $test->fresh();
}

function startAutosaveAttempt(User $student, ReadingTest $test): ReadingAttempt
{
    $response = test()->actingAs($student)->get(route('reading-tests.start', $test));
    $response->assertOk();

    return ReadingAttempt::query()
        ->where('user_id', $student->id)
        ->where('reading_test_id', $test->id)
        ->where('status', TestAttemptStatus::InProgress)
        ->firstOrFail();
}

it('creates or resumes in progress attempt when starting test', function (): void {
    $student = autosaveStudent();
    $test = createAutosaveReadingTest();

    $attempt = startAutosaveAttempt($student, $test);

    expect($attempt->status)->toBe(TestAttemptStatus::InProgress);
    expect($attempt->started_at)->not->toBeNull();
    expect($attempt->current_passage_id)->not->toBeNull();
    expect($attempt->current_question_id)->not->toBeNull();

    startAutosaveAttempt($student, $test);

    expect(ReadingAttempt::query()->where('user_id', $student->id)->where('reading_test_id', $test->id)->count())->toBe(1);
});

it('saves tfng answer and restores after refresh', function (): void {
    $student = autosaveStudent();
    $test = createAutosaveReadingTest();
    $attempt = startAutosaveAttempt($student, $test);

    $question = $test->questions()->where('question_number', 1)->firstOrFail();
    $group = $question->group;
    $passage = $group->passage;

    $this->actingAs($student)->postJson(route('reading-attempts.answers.store', $attempt), [
        'question_id' => $question->id,
        'question_number' => 1,
        'question_type' => OfficialReadingQuestionType::TrueFalseNotGiven->value,
        'passage_id' => $passage->id,
        'group_id' => $group->id,
        'answer' => 'TRUE',
    ])->assertOk()
        ->assertJsonPath('data.success', true)
        ->assertJsonPath('data.answered_status', 'answered');

    $saved = ReadingAnswer::query()->where('attempt_id', $attempt->id)->where('question_id', $question->id)->first();
    expect($saved?->answer)->toBe('TRUE');
    expect($saved?->is_correct)->toBeNull();

    $this->actingAs($student)
        ->get(route('reading-tests.start', $test))
        ->assertOk()
        ->assertSee('data-question-number="1"', false);
});

it('saves completion style text answer with debounce friendly payload', function (): void {
    $student = autosaveStudent();
    $test = createAutosaveReadingTest('autosave-short-answer');
    $attempt = startAutosaveAttempt($student, $test);

    $question = $test->questions()->where('question_number', 3)->firstOrFail();

    $this->actingAs($student)->postJson(route('reading-attempts.answers.store', $attempt), [
        'question_id' => $question->id,
        'question_number' => 3,
        'question_type' => OfficialReadingQuestionType::ShortAnswer->value,
        'passage_id' => $question->group->passage_id,
        'group_id' => $question->group_id,
        'answer' => 'steam engine',
    ])->assertOk();

    expect(ReadingAnswer::query()->where('attempt_id', $attempt->id)->where('question_id', $question->id)->value('answer'))
        ->toBe('steam engine');
});

it('saves mcq multiple answers as json array', function (): void {
    $student = autosaveStudent();
    $admin = createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);

    $test = ReadingTest::query()->create([
        'title' => 'MCQ Multiple Autosave',
        'slug' => 'mcq-multiple-autosave',
        'exam_type' => ExamType::Academic,
        'duration_minutes' => 60,
        'status' => PublishStatus::Published,
        'published_at' => now(),
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $passage = $test->passages()->create([
        'part_number' => 1,
        'title' => 'Passage',
        'start_question' => 1,
        'end_question' => 2,
        'content_html' => '<p>Body</p>',
        'status' => PassageStatus::Published,
        'sort_order' => 1,
    ]);

    $group = $passage->groups()->create([
        'title' => 'Q1-2',
        'instruction' => 'Choose TWO letters, A-E.',
        'question_type' => OfficialReadingQuestionType::MultipleChoiceMultiple,
        'start_question' => 1,
        'end_question' => 2,
        'sort_order' => 1,
        'status' => PassageStatus::Published,
    ]);

    $question = $group->questions()->create([
        'question_number' => 1,
        'prompt' => 'Choose two',
        'marks' => 1,
        'sort_order' => 1,
    ]);

    $attempt = startAutosaveAttempt($student, $test);

    $this->actingAs($student)->postJson(route('reading-attempts.answers.store', $attempt), [
        'question_id' => $question->id,
        'question_number' => 1,
        'question_type' => OfficialReadingQuestionType::MultipleChoiceMultiple->value,
        'passage_id' => $passage->id,
        'group_id' => $group->id,
        'answer_json' => ['A', 'C'],
    ])->assertOk()
        ->assertJsonPath('data.answered_count', 2)
        ->assertJsonPath('data.navigator_status.questions.1.answered', true)
        ->assertJsonPath('data.navigator_status.questions.2.answered', true);

    $saved = ReadingAnswer::query()->where('attempt_id', $attempt->id)->where('question_id', $question->id)->first();
    expect($saved?->answer_json)->toBe(['A', 'C']);
    expect($saved?->answer)->toBeNull();
});

it('counts partial mcq multiple selections as unanswered until required count is met', function (): void {
    $student = autosaveStudent();
    $admin = createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);

    $test = ReadingTest::query()->create([
        'title' => 'MCQ Multiple Partial',
        'slug' => 'mcq-multiple-partial-'.uniqid(),
        'exam_type' => ExamType::Academic,
        'duration_minutes' => 60,
        'status' => PublishStatus::Published,
        'published_at' => now(),
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $passage = $test->passages()->create([
        'part_number' => 1,
        'title' => 'Passage',
        'start_question' => 1,
        'end_question' => 2,
        'content_html' => '<p>Body</p>',
        'status' => PassageStatus::Published,
        'sort_order' => 1,
    ]);

    $group = $passage->groups()->create([
        'title' => 'Q1-2',
        'instruction' => 'Choose TWO letters, A-E.',
        'question_type' => OfficialReadingQuestionType::MultipleChoiceMultiple,
        'start_question' => 1,
        'end_question' => 2,
        'sort_order' => 1,
        'status' => PassageStatus::Published,
    ]);

    $question = $group->questions()->create([
        'question_number' => 1,
        'prompt' => 'Choose two',
        'marks' => 1,
        'sort_order' => 1,
    ]);

    $attempt = startAutosaveAttempt($student, $test);

    $this->actingAs($student)->postJson(route('reading-attempts.answers.store', $attempt), [
        'question_id' => $question->id,
        'question_number' => 1,
        'question_type' => OfficialReadingQuestionType::MultipleChoiceMultiple->value,
        'passage_id' => $passage->id,
        'group_id' => $group->id,
        'answer_json' => ['A'],
    ])->assertOk()
        ->assertJsonPath('data.answered_count', 0)
        ->assertJsonPath('data.navigator_status.questions.1.answered', false)
        ->assertJsonPath('data.navigator_status.questions.2.answered', false)
        ->assertJsonPath('data.answered_status', 'unanswered');
});

it('marks every number in each mcq multiple group answered when selections are complete', function (): void {
    $student = autosaveStudent();
    $admin = createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);

    $test = ReadingTest::query()->create([
        'title' => 'MCQ Multiple Dual Groups',
        'slug' => 'mcq-multiple-dual-'.uniqid(),
        'exam_type' => ExamType::Academic,
        'duration_minutes' => 60,
        'status' => PublishStatus::Published,
        'published_at' => now(),
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $passage = $test->passages()->create([
        'part_number' => 1,
        'title' => 'Passage',
        'start_question' => 1,
        'end_question' => 4,
        'content_html' => '<p>Body</p>',
        'status' => PassageStatus::Published,
        'sort_order' => 1,
    ]);

    foreach ([
        ['start' => 1, 'end' => 2, 'number' => 2, 'answers' => ['A', 'B']],
        ['start' => 3, 'end' => 4, 'number' => 4, 'answers' => ['C', 'E']],
    ] as $config) {
        $group = $passage->groups()->create([
            'title' => "Q{$config['start']}-{$config['end']}",
            'instruction' => 'Choose TWO letters, A-E.',
            'question_type' => OfficialReadingQuestionType::MultipleChoiceMultiple,
            'start_question' => $config['start'],
            'end_question' => $config['end'],
            'sort_order' => $config['start'],
            'status' => PassageStatus::Published,
        ]);

        $group->questions()->create([
            'question_number' => $config['number'],
            'prompt' => 'Choose two',
            'marks' => 1,
            'sort_order' => 1,
        ]);
    }

    $attempt = startAutosaveAttempt($student, $test);

    foreach ([2 => ['A', 'B'], 4 => ['C', 'E']] as $questionNumber => $answers) {
        $question = $test->questions()->where('question_number', $questionNumber)->firstOrFail();

        $this->actingAs($student)->postJson(route('reading-attempts.answers.store', $attempt), [
            'question_id' => $question->id,
            'question_number' => $questionNumber,
            'question_type' => OfficialReadingQuestionType::MultipleChoiceMultiple->value,
            'passage_id' => $passage->id,
            'group_id' => $question->group_id,
            'answer_json' => $answers,
        ])->assertOk();
    }

    $navigator = app(\App\Services\Exam\ReadingAnswerService::class)
        ->buildNavigatorStatus($attempt->fresh());

    expect($navigator['answered_questions'])->toMatchArray([
        1 => true,
        2 => true,
        3 => true,
        4 => true,
    ]);
    expect($navigator['answered_count'])->toBe(4);
});

it('saves and restores position across refresh', function (): void {
    $student = autosaveStudent();
    $test = createAutosaveReadingTest();
    $attempt = startAutosaveAttempt($student, $test);

    $question = $test->questions()->where('question_number', 3)->firstOrFail();
    $passage = $question->group->passage;

    $this->actingAs($student)->postJson(route('reading-attempts.position', $attempt), [
        'current_passage' => $passage->id,
        'current_question' => $question->id,
    ])->assertOk()
        ->assertJsonPath('data.current_question_number', 3);

    $attempt->refresh();
    expect($attempt->current_passage_id)->toBe($passage->id);
    expect($attempt->current_question_id)->toBe($question->id);

    $this->actingAs($student)
        ->get(route('reading-tests.start', $test))
        ->assertOk()
        ->assertSee('initialQuestionNumber', false)
        ->assertSee('Passage Two', false);
});

it('toggles question flag and persists after refresh', function (): void {
    $student = autosaveStudent();
    $test = createAutosaveReadingTest();
    $attempt = startAutosaveAttempt($student, $test);
    $question = $test->questions()->where('question_number', 3)->firstOrFail();

    $this->actingAs($student)->postJson(route('reading-attempts.answers.flag', [$attempt, $question]), [
        'flagged' => true,
    ])->assertOk()
        ->assertJsonPath('data.answered_status', 'flagged');

    expect(ReadingAnswer::query()->where('attempt_id', $attempt->id)->where('question_id', $question->id)->value('flagged'))
        ->toBeTruthy();

    $payload = app(\App\Services\Exam\ReadingAnswerService::class)
        ->buildAttemptPayload($attempt->fresh(), $test->fresh());

    expect($payload['savedAnswers'][$question->id]['flagged'] ?? false)->toBeTrue();
});

it('blocks saving answers for another users attempt', function (): void {
    $owner = autosaveStudent();
    $intruder = createUserWithRole(UserRole::Student, ['email_verified_at' => now()]);
    $test = createAutosaveReadingTest();
    $attempt = startAutosaveAttempt($owner, $test);
    $question = $test->questions()->firstOrFail();

    $this->actingAs($intruder)->postJson(route('reading-attempts.answers.store', $attempt), [
        'question_id' => $question->id,
        'question_number' => $question->question_number,
        'question_type' => $question->group->question_type->value,
        'passage_id' => $question->group->passage_id,
        'group_id' => $question->group_id,
        'answer' => 'TRUE',
    ])->assertForbidden();
});

it('returns navigator status with part answered counts', function (): void {
    $student = autosaveStudent();
    $test = createAutosaveReadingTest();
    $attempt = startAutosaveAttempt($student, $test);
    $question = $test->questions()->where('question_number', 1)->firstOrFail();

    $response = $this->actingAs($student)->postJson(route('reading-attempts.answers.store', $attempt), [
        'question_id' => $question->id,
        'question_number' => 1,
        'question_type' => OfficialReadingQuestionType::TrueFalseNotGiven->value,
        'passage_id' => $question->group->passage_id,
        'group_id' => $question->group_id,
        'answer' => 'NOT_GIVEN',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.answered_count', 1)
        ->assertJsonPath('data.navigator_status.parts.'.$question->group->passage_id.'.answered', 1);
});
