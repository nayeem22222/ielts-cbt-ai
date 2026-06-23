<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Commerce\IeltsModule;
use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\ReadingQuestionType;
use App\Enums\Exam\ResultStatus;
use App\Enums\Exam\ScoringMethod;
use App\Enums\Exam\TestAttemptStatus;
use App\Models\ExamTest;
use App\Models\Question;
use App\Models\Result;
use App\Models\ResultQuestionScore;
use App\Models\ResultStatistics;
use App\Models\StudentAnswer;
use App\Models\TestAttempt;
use App\Services\Admin\Exam\ReadingTestBuilderService;
use App\Services\Exam\Scoring\ReadingAnswerMatcher;
use App\Services\Exam\Scoring\ReadingBandConverter;
use App\Services\Exam\Scoring\ReadingScoringEngine;

beforeEach(function (): void {
    seedRbac();
});

function createScoringReadingTest(array $questions): ExamTest
{
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'scoring-admin@example.com',
        'email_verified_at' => now(),
    ]);

    $test = app(ReadingTestBuilderService::class)->importTest([
        'test' => [
            'title' => 'Scoring Engine Test',
            'slug' => 'scoring-engine-test-'.uniqid(),
            'exam_type' => ExamType::Academic->value,
            'duration_seconds' => 3600,
            'status' => PublishStatus::Published->value,
        ],
        'passages' => [[
            'title' => 'Passage 1',
            'sort_order' => 1,
            'stimulus_text' => 'Sample passage for scoring.',
            'questions' => $questions,
        ]],
    ], $admin);

    $test->update(['status' => PublishStatus::Published, 'published_at' => now()]);

    return $test->fresh();
}

function createReadingAttemptWithAnswers(ExamTest $test, array $answersByQuestionNumber): TestAttempt
{
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'scoring-student-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    assignStudentPackage($student, createDemoPackage([
        'slug' => 'scoring-package-'.uniqid(),
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

    foreach ($answersByQuestionNumber as $number => $payload) {
        $question = $questions->get($number);

        if ($question === null) {
            continue;
        }

        StudentAnswer::query()->create([
            'test_attempt_id' => $attempt->id,
            'test_section_id' => $section->id,
            'question_id' => $question->id,
            'module' => IeltsModule::Reading->value,
            'answer_text' => $payload['answer_text'] ?? null,
            'selected_options' => $payload['selected_options'] ?? null,
            'is_flagged' => $payload['is_flagged'] ?? false,
        ]);
    }

    return $attempt->fresh(['answers']);
}

it('scores all fifteen official reading question types', function (): void {
    $types = ReadingQuestionType::cases();
    $questions = [];

    foreach ($types as $index => $type) {
        $questions[] = [
            'type' => $type->value,
            'question_number' => $index + 1,
            'prompt' => "Prompt for {$type->label()}",
            'marks' => 1,
            'sort_order' => $index + 1,
            'options' => $type->usesOptions() ? ['Alpha', 'Beta', 'Gamma'] : [],
            'correct_answer' => match ($type) {
                ReadingQuestionType::MultipleChoiceMultiple => ['Alpha', 'Gamma'],
                ReadingQuestionType::MultipleChoiceSingle,
                ReadingQuestionType::MatchingHeadings,
                ReadingQuestionType::MatchingInformation,
                ReadingQuestionType::MatchingFeatures,
                ReadingQuestionType::MatchingSentenceEndings => 'Beta',
                ReadingQuestionType::TrueFalseNg => 'False',
                ReadingQuestionType::YesNoNg => 'Yes',
                default => 'keyword',
            },
        ];
    }

    $test = createScoringReadingTest($questions);
    $matcher = app(ReadingAnswerMatcher::class);

    $answersByNumber = [];
    foreach ($types as $index => $type) {
        $answersByNumber[$index + 1] = match ($type) {
            ReadingQuestionType::MultipleChoiceMultiple => ['answer_text' => 'Alpha, Gamma'],
            ReadingQuestionType::MultipleChoiceSingle,
            ReadingQuestionType::MatchingHeadings,
            ReadingQuestionType::MatchingInformation,
            ReadingQuestionType::MatchingFeatures,
            ReadingQuestionType::MatchingSentenceEndings => ['answer_text' => 'Beta'],
            ReadingQuestionType::TrueFalseNg => ['answer_text' => 'False'],
            ReadingQuestionType::YesNoNg => ['answer_text' => 'Yes'],
            default => ['answer_text' => 'keyword'],
        };
    }

    $attempt = createReadingAttemptWithAnswers($test, $answersByNumber);

    $result = app(ReadingScoringEngine::class)->scoreAttempt($attempt);

    expect($result->status)->toBe(ResultStatus::Computed);
    expect($result->questionScores)->toHaveCount(15);
    expect($result->questionScores->where('is_correct', true))->toHaveCount(15);
    expect((float) $result->raw_score)->toBe(15.0);
    expect($result->statistics)->not->toBeNull();
    expect($result->statistics->by_question_type)->toHaveCount(15);
});

it('applies partial marks for multiple choice multiple answers', function (): void {
    $test = createScoringReadingTest([[
        'type' => ReadingQuestionType::MultipleChoiceMultiple->value,
        'question_number' => 1,
        'prompt' => 'Choose TWO answers',
        'marks' => 2,
        'sort_order' => 1,
        'options' => ['Solar', 'Wind', 'Coal', 'Gas'],
        'correct_answer' => ['Solar', 'Wind'],
    ]]);

    $attempt = createReadingAttemptWithAnswers($test, [
        1 => ['answer_text' => 'Solar'],
    ]);

    $result = app(ReadingScoringEngine::class)->scoreAttempt($attempt);
    $item = $result->questionScores->first();

    expect($item->is_correct)->toBeFalse();
    expect((float) $item->partial_ratio)->toBe(0.5);
    expect((float) $item->score_awarded)->toBe(1.0);
    expect((float) $result->raw_score)->toBe(1.0);
    expect($result->statistics->partial_count)->toBe(1);
});

it('calculates reading band using academic conversion scaled to test size', function (): void {
    $converter = app(ReadingBandConverter::class);

    expect($converter->bandFromRawOutOf40(40))->toBe(9.0);
    expect($converter->bandFromRawOutOf40(30))->toBe(7.0);
    expect($converter->bandFromRawOutOf40(20))->toBe(5.5);
    expect($converter->bandFromScores(2, 2))->toBe(9.0);
    expect($converter->bandFromScores(1, 2))->toBe(5.5);
});

it('stores detailed result question scores and module band score', function (): void {
    $test = createScoringReadingTest([
        [
            'type' => ReadingQuestionType::TrueFalseNg->value,
            'question_number' => 1,
            'prompt' => 'Statement one',
            'marks' => 1,
            'sort_order' => 1,
            'correct_answer' => 'True',
        ],
        [
            'type' => ReadingQuestionType::ShortAnswer->value,
            'question_number' => 2,
            'prompt' => 'Keyword question',
            'marks' => 1,
            'sort_order' => 2,
            'correct_answer' => 'energy',
        ],
    ]);

    $attempt = createReadingAttemptWithAnswers($test, [
        1 => ['answer_text' => 'True'],
        2 => ['answer_text' => 'wrong'],
    ]);

    $result = app(ReadingScoringEngine::class)->scoreAttempt($attempt);

    expect(Result::query()->where('test_attempt_id', $attempt->id)->exists())->toBeTrue();
    expect(ResultQuestionScore::query()->where('result_id', $result->id)->count())->toBe(2);
    expect($result->bandScores)->toHaveCount(1);
    expect($result->bandScores->first()->module)->toBe(IeltsModule::Reading->value);
    expect($result->bandScores->first()->scoring_method)->toBe(ScoringMethod::Auto);
    expect($attempt->fresh()->status)->toBe(TestAttemptStatus::Completed);
    expect((float) $result->overall_band)->toBe(5.5);
});

it('generates statistics grouped by question type and passage', function (): void {
    $test = createScoringReadingTest([
        [
            'type' => ReadingQuestionType::TrueFalseNg->value,
            'question_number' => 1,
            'prompt' => 'TFNG',
            'marks' => 1,
            'sort_order' => 1,
            'correct_answer' => 'True',
        ],
        [
            'type' => ReadingQuestionType::ShortAnswer->value,
            'question_number' => 2,
            'prompt' => 'Short',
            'marks' => 1,
            'sort_order' => 2,
            'correct_answer' => 'energy',
        ],
    ]);

    $attempt = createReadingAttemptWithAnswers($test, [
        1 => ['answer_text' => 'True', 'is_flagged' => true],
        2 => ['answer_text' => 'energy'],
    ]);

    $result = app(ReadingScoringEngine::class)->scoreAttempt($attempt);
    /** @var ResultStatistics $stats */
    $stats = $result->statistics;

    expect($stats->total_questions)->toBe(2);
    expect($stats->answered_count)->toBe(2);
    expect($stats->correct_count)->toBe(2);
    expect($stats->flagged_count)->toBe(1);
    expect((float) $stats->accuracy_percent)->toBe(100.0);
    expect($stats->by_question_type)->toHaveKey(ReadingQuestionType::TrueFalseNg->value);
    expect($stats->by_passage)->not->toBeEmpty();
});

it('submits reading attempt through student route and returns scored result', function (): void {
    $test = createScoringReadingTest([
        [
            'type' => ReadingQuestionType::YesNoNg->value,
            'question_number' => 1,
            'prompt' => 'Writer agrees?',
            'marks' => 1,
            'sort_order' => 1,
            'correct_answer' => 'No',
        ],
    ]);

    $attempt = createReadingAttemptWithAnswers($test, [
        1 => ['answer_text' => 'No'],
    ]);

    $this->actingAs($attempt->user)
        ->postJson(route('exam.reading.submit', $attempt))
        ->assertOk()
        ->assertJsonPath('data.overall_band', 9)
        ->assertJsonPath('data.raw_score', 1);
});

it('normalizes text completion answers case insensitively', function (): void {
    $test = createScoringReadingTest([[
        'type' => ReadingQuestionType::SentenceCompletion->value,
        'question_number' => 1,
        'prompt' => 'Complete the sentence',
        'marks' => 1,
        'sort_order' => 1,
        'correct_answer' => 'Renewable Energy',
    ]]);

    $question = Question::query()->where('question_number', 1)->firstOrFail();
    $outcome = app(ReadingAnswerMatcher::class)->score(
        $question->load('correctAnswer', 'options'),
        new StudentAnswer(['answer_text' => '  renewable   energy '])
    );

    expect($outcome->isCorrect)->toBeTrue();
    expect($outcome->scoreAwarded)->toBe(1.0);
});

it('does not duplicate results when scoring the same attempt twice', function (): void {
    $test = createScoringReadingTest([[
        'type' => ReadingQuestionType::ShortAnswer->value,
        'question_number' => 1,
        'prompt' => 'Answer',
        'marks' => 1,
        'sort_order' => 1,
        'correct_answer' => 'test',
    ]]);

    $attempt = createReadingAttemptWithAnswers($test, [
        1 => ['answer_text' => 'test'],
    ]);

    $engine = app(ReadingScoringEngine::class);
    $first = $engine->scoreAttempt($attempt);
    $second = $engine->scoreAttempt($attempt->fresh());

    expect($first->id)->toBe($second->id);
    expect(Result::query()->where('test_attempt_id', $attempt->id)->count())->toBe(1);
});

it('creates result scoring detail tables', function (): void {
    expect(\Illuminate\Support\Facades\Schema::hasTable('result_question_scores'))->toBeTrue();
    expect(\Illuminate\Support\Facades\Schema::hasTable('result_statistics'))->toBeTrue();
});
