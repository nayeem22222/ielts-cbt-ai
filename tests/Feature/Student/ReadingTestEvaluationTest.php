<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Commerce\IeltsModule;
use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\OfficialReadingQuestionType;
use App\Enums\Exam\PassageStatus;
use App\Enums\Exam\ReadingCompletionAnswerRule;
use App\Enums\Exam\TestAttemptStatus;
use App\Models\ReadingAttempt;
use App\Models\ReadingCorrectAnswer;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingQuestionOption;
use App\Models\ReadingTest;
use App\Models\User;
use App\Services\Exam\ReadingEvaluationService;

beforeEach(function (): void {
    seedRbac();
    test()->withoutVite();
});

function evaluationStudent(): User
{
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'reading-eval-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    assignStudentPackage($student, createDemoPackage([
        'slug' => 'reading-eval-package-'.uniqid(),
        'module_access' => [IeltsModule::Reading->value],
        'is_public' => true,
        'is_active' => true,
    ]));

    return $student;
}

function createEvaluationReadingTest(): ReadingTest
{
    $admin = createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);

    $test = ReadingTest::query()->create([
        'title' => 'Evaluation Engine Test',
        'slug' => 'evaluation-engine-'.uniqid(),
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
        'title' => 'Evaluation Passage',
        'start_question' => 1,
        'end_question' => 10,
        'content_html' => '<p>Passage body</p>',
        'status' => PassageStatus::Published,
        'sort_order' => 1,
    ]);

    $configs = [
        [OfficialReadingQuestionType::TrueFalseNotGiven, 1, 'TRUE', []],
        [OfficialReadingQuestionType::YesNoNotGiven, 2, 'YES', []],
        [OfficialReadingQuestionType::MultipleChoiceSingle, 3, 'B', ['A', 'B', 'C', 'D']],
        [OfficialReadingQuestionType::MultipleChoiceMultiple, 4, ['A', 'D'], ['A', 'B', 'C', 'D']],
        [OfficialReadingQuestionType::MatchingInformation, 5, 'C', ['A', 'B', 'C', 'D']],
        [OfficialReadingQuestionType::SummaryCompletion, 6, 'Industrial Revolution', []],
        [OfficialReadingQuestionType::ShortAnswer, 7, 'steam engine', []],
        [OfficialReadingQuestionType::ShortAnswer, 8, 'one word', [], ReadingCompletionAnswerRule::OneWordOnly->value],
        [OfficialReadingQuestionType::TrueFalseNotGiven, 9, 'FALSE', []],
        [OfficialReadingQuestionType::TrueFalseNotGiven, 10, 'TRUE', []],
    ];

    foreach ($configs as $config) {
        [$type, $number, $correct, $options] = array_pad($config, 4, []);
        $wordLimit = $config[4] ?? ReadingCompletionAnswerRule::ThreeWords->value;

        $group = ReadingQuestionGroup::query()->firstOrCreate(
            [
                'passage_id' => $passage->id,
                'question_type' => $type,
                'start_question' => $number,
            ],
            [
                'title' => "Q{$number}",
                'end_question' => $number,
                'sort_order' => $number,
                'status' => PassageStatus::Published,
                'settings' => $type === OfficialReadingQuestionType::ShortAnswer && $number === 8
                    ? ['answer_rule' => $wordLimit]
                    : null,
            ],
        );

        /** @var ReadingQuestion $question */
        $question = $group->questions()->create([
            'question_number' => $number,
            'prompt' => "Prompt {$number}",
            'marks' => 1,
            'sort_order' => 1,
        ]);

        foreach ($options as $index => $key) {
            ReadingQuestionOption::query()->create([
                'group_id' => in_array($type, [OfficialReadingQuestionType::MatchingInformation], true) ? $group->id : null,
                'question_id' => in_array($type, [OfficialReadingQuestionType::MatchingInformation], true) ? null : $question->id,
                'option_key' => $key,
                'option_label' => "Option {$key}",
                'sort_order' => $index + 1,
            ]);
        }

        if ($type === OfficialReadingQuestionType::MultipleChoiceMultiple) {
            ReadingCorrectAnswer::query()->create([
                'question_id' => $question->id,
                'answer' => $correct[0],
                'answer_json' => $correct,
            ]);
        } elseif ($type->isCompletionBuilderType() || $type === OfficialReadingQuestionType::ShortAnswer) {
            ReadingCorrectAnswer::query()->create([
                'question_id' => $question->id,
                'answer' => is_array($correct) ? $correct[0] : $correct,
                'answer_json' => [
                    'answers' => is_array($correct) ? $correct : [$correct],
                    'alternatives' => $number === 6 ? ['industrial revolution'] : [],
                    'case_sensitive' => false,
                    'word_limit' => $wordLimit,
                ],
            ]);
        } else {
            ReadingCorrectAnswer::query()->create([
                'question_id' => $question->id,
                'answer' => is_array($correct) ? $correct[0] : $correct,
                'matching_key' => is_array($correct) ? $correct[0] : $correct,
            ]);
        }
    }

    return $test->fresh();
}

function startEvaluationAttempt(User $student, ReadingTest $test): ReadingAttempt
{
    test()->actingAs($student)->get(route('reading-tests.start', $test))->assertOk();

    return ReadingAttempt::query()
        ->where('user_id', $student->id)
        ->where('reading_test_id', $test->id)
        ->where('status', TestAttemptStatus::InProgress)
        ->firstOrFail();
}

function answerQuestion(ReadingAttempt $attempt, ReadingQuestion $question, array $payload): void
{
    $group = $question->group;
    $passage = $group->passage;

    test()->actingAs($attempt->user)->postJson(route('reading-attempts.answers.store', $attempt), array_merge([
        'question_id' => $question->id,
        'question_number' => $question->question_number,
        'question_type' => $group->question_type->value,
        'passage_id' => $passage->id,
        'group_id' => $group->id,
    ], $payload))->assertOk();
}

it('evaluates on submit and calculates raw score and band once', function (): void {
    $student = evaluationStudent();
    $test = createEvaluationReadingTest();
    $attempt = startEvaluationAttempt($student, $test);

    $answers = [
        1 => ['answer' => 'TRUE'],
        2 => ['answer' => 'YES'],
        3 => ['answer' => 'B'],
        4 => ['answer_json' => ['D', 'A']],
        5 => ['answer' => 'C'],
        6 => ['answer' => ' Industrial Revolution '],
        7 => ['answer' => 'steam-engine'],
        8 => ['answer' => 'one word'],
        9 => ['answer' => 'TRUE'],
    ];

    foreach ($answers as $number => $payload) {
        $question = $test->questions()->where('question_number', $number)->firstOrFail();
        answerQuestion($attempt, $question, $payload);
    }

    $this->actingAs($student)
        ->postJson(route('reading-attempts.submit', $attempt))
        ->assertOk()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.redirect_url', route('reading-attempts.result', $attempt));

    $attempt->refresh();
    expect($attempt->status)->toBe(TestAttemptStatus::Completed);
    expect((float) $attempt->score)->toBe(7.0);
    expect((float) $attempt->band)->toBe(6.5);
    expect($attempt->evaluated_at)->not->toBeNull();

    $firstEvaluatedAt = $attempt->evaluated_at;
    app(ReadingEvaluationService::class)->evaluateAttempt($attempt);
    $attempt->refresh();
    expect($attempt->evaluated_at?->eq($firstEvaluatedAt))->toBeTrue();
});

it('marks tfng ynng mcq matching and completion answers correctly', function (): void {
    $student = evaluationStudent();
    $test = createEvaluationReadingTest();
    $attempt = startEvaluationAttempt($student, $test);

    answerQuestion($attempt, $test->questions()->where('question_number', 1)->firstOrFail(), ['answer' => 'TRUE']);
    answerQuestion($attempt, $test->questions()->where('question_number', 2)->firstOrFail(), ['answer' => 'NO']);
    answerQuestion($attempt, $test->questions()->where('question_number', 3)->firstOrFail(), ['answer' => 'B']);
    answerQuestion($attempt, $test->questions()->where('question_number', 4)->firstOrFail(), ['answer_json' => ['A', 'D']]);
    answerQuestion($attempt, $test->questions()->where('question_number', 5)->firstOrFail(), ['answer' => 'C']);
    answerQuestion($attempt, $test->questions()->where('question_number', 6)->firstOrFail(), ['answer' => 'industrial revolution']);
    answerQuestion($attempt, $test->questions()->where('question_number', 7)->firstOrFail(), ['answer' => 'steam engine']);

    $this->actingAs($student)->postJson(route('reading-attempts.submit', $attempt))->assertOk();

    $attempt->refresh();
    expect((float) $attempt->score)->toBe(6.0);

    $q2 = $attempt->answers()->whereHas('question', fn ($q) => $q->where('question_number', 2))->first();
    expect($q2?->is_correct)->toBeFalse();
});

it('marks word limit violations and empty answers as incorrect or unanswered', function (): void {
    $student = evaluationStudent();
    $test = createEvaluationReadingTest();
    $attempt = startEvaluationAttempt($student, $test);

    answerQuestion($attempt, $test->questions()->where('question_number', 8)->firstOrFail(), ['answer' => 'two word answer']);

    $this->actingAs($student)->postJson(route('reading-attempts.submit', $attempt))->assertOk();

    $q8 = $attempt->answers()->whereHas('question', fn ($q) => $q->where('question_number', 8))->first();
    expect($q8?->is_correct)->toBeFalse();

    $q10 = $attempt->answers()->whereHas('question', fn ($q) => $q->where('question_number', 10))->first();
    expect($q10?->is_correct)->toBeFalse();
    expect($q10?->evaluation_json['status'] ?? null)->toBe('unanswered');

    $attempt->refresh();
    expect((int) ($attempt->metadata['evaluation']['unanswered'] ?? 0))->toBeGreaterThan(0);
});

it('shows result summary and question review pages', function (): void {
    $student = evaluationStudent();
    $test = createEvaluationReadingTest();
    $attempt = startEvaluationAttempt($student, $test);

    answerQuestion($attempt, $test->questions()->where('question_number', 1)->firstOrFail(), ['answer' => 'TRUE']);
    $this->actingAs($student)->postJson(route('reading-attempts.submit', $attempt))->assertOk();

    $this->actingAs($student)
        ->get(route('reading-attempts.result', $attempt))
        ->assertOk()
        ->assertSee('Band Score', false)
        ->assertSee('Raw Score', false)
        ->assertSee($test->title, false);

    $this->actingAs($student)
        ->get(route('reading-attempts.result.review', $attempt))
        ->assertOk()
        ->assertSee('Explanation Review', false)
        ->assertSee('Your Answer', false)
        ->assertSee('Correct Answer', false);
});

it('blocks students from viewing another students result', function (): void {
    $owner = evaluationStudent();
    $other = evaluationStudent();
    $test = createEvaluationReadingTest();
    $attempt = startEvaluationAttempt($owner, $test);

    $this->actingAs($owner)->postJson(route('reading-attempts.submit', $attempt))->assertOk();

    $this->actingAs($other)
        ->get(route('reading-attempts.result', $attempt))
        ->assertForbidden();
});

it('blocks viewing result for in progress attempts', function (): void {
    $student = evaluationStudent();
    $test = createEvaluationReadingTest();
    $attempt = startEvaluationAttempt($student, $test);

    $this->actingAs($student)
        ->get(route('reading-attempts.result', $attempt))
        ->assertRedirect(route('reading-tests.start', $test));
});

it('allows admin to re-evaluate submitted attempts', function (): void {
    $student = evaluationStudent();
    $admin = createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);
    $test = createEvaluationReadingTest();
    $attempt = startEvaluationAttempt($student, $test);

    answerQuestion($attempt, $test->questions()->where('question_number', 1)->firstOrFail(), ['answer' => 'FALSE']);
    $this->actingAs($student)->postJson(route('reading-attempts.submit', $attempt))->assertOk();

    $this->actingAs($admin)
        ->post(route('admin.reading-attempts.re-evaluate', $attempt))
        ->assertRedirect();

    expect((float) $attempt->fresh()->score)->toBe(0.0);
});

it('allows students to start a new attempt after completing a previous one', function (): void {
    $student = evaluationStudent();
    $test = createEvaluationReadingTest();
    $firstAttempt = startEvaluationAttempt($student, $test);

    $this->actingAs($student)->postJson(route('reading-attempts.submit', $firstAttempt))->assertOk();
    expect($firstAttempt->fresh()->status)->toBe(TestAttemptStatus::Completed);

    $this->actingAs($student)
        ->get(route('reading-tests.show', $test))
        ->assertOk()
        ->assertSee('Start New Attempt', false)
        ->assertSee('View Latest Result', false);

    $this->actingAs($student)
        ->get(route('reading-tests.start', $test))
        ->assertOk()
        ->assertSee('data-test-id', false);

    $attempts = ReadingAttempt::query()
        ->where('user_id', $student->id)
        ->where('reading_test_id', $test->id)
        ->orderBy('id')
        ->get();

    expect($attempts)->toHaveCount(2);
    expect($attempts->first()?->status)->toBe(TestAttemptStatus::Completed);
    expect($attempts->last()?->status)->toBe(TestAttemptStatus::InProgress);
});
