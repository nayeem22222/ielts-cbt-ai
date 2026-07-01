<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Listening\ListeningAnswerFormat;
use App\Enums\Listening\ListeningAttemptStatus;
use App\Enums\Listening\ListeningEvaluationStatus;
use App\Enums\Listening\ListeningEvaluationType;
use App\Enums\Listening\ListeningMatchStatus;
use App\Enums\Listening\ListeningQuestionType;
use App\Enums\Listening\ListeningTestStatus;
use App\Enums\Listening\ListeningTestType;
use App\Jobs\Listening\Evaluation\EvaluateListeningAttemptJob;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningAttemptAnswer;
use App\Models\Listening\ListeningAttemptAnswerEvaluation;
use App\Models\Listening\ListeningAttemptEvaluation;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Enums\Listening\ListeningLayoutType;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Models\User;
use App\Actions\Listening\Evaluation\EvaluateListeningAttemptAction;
use App\Actions\Listening\Evaluation\ReEvaluateListeningAttemptAction;
use App\Services\Listening\Evaluation\Evaluators\CompletionAnswerEvaluator;
use App\Services\Listening\Evaluation\Evaluators\LabellingAnswerEvaluator;
use App\Services\Listening\Evaluation\Evaluators\LetterAnswerEvaluator;
use App\Services\Listening\Evaluation\Evaluators\MatchingAnswerEvaluator;
use App\Services\Listening\Evaluation\Evaluators\MultipleAnswerEvaluator;
use App\Services\Listening\Evaluation\Evaluators\TextAnswerEvaluator;
use App\Services\Listening\Evaluation\ListeningAnswerEngineService;
use App\Services\Listening\Evaluation\ListeningAnswerNormalizationService;
use App\Services\Listening\Evaluation\ListeningBandScoreService;
use App\Support\Listening\Evaluation\ListeningMatchReason;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    seedRbac();
    config([
        'listening.answer_engine.mode' => 'sync',
        'listening.answer_engine.evaluate_on_submit' => true,
        'listening.answer_engine.multiple_answer.partial_marking' => false,
    ]);
});

function listeningEvaluationAdmin(): User
{
    return createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);
}

function listeningEvaluationStudent(): User
{
    return createUserWithRole(UserRole::Student, ['email_verified_at' => now()]);
}

function listeningEvaluationTest(): ListeningTest
{
    $admin = listeningEvaluationAdmin();

    return ListeningTest::query()->create([
        'title' => 'Eval Test '.uniqid(),
        'slug' => 'eval-'.uniqid(),
        'test_code' => 'LST-EVL-'.strtoupper(uniqid()),
        'test_type' => ListeningTestType::Academic,
        'status' => ListeningTestStatus::Published,
        'is_active' => true,
        'duration_minutes' => 30,
        'transfer_time_minutes' => 10,
        'total_sections' => 1,
        'total_questions' => 5,
        'published_at' => now(),
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);
}

function listeningEvaluationSection(ListeningTest $test, int $start = 1, int $end = 10): ListeningSection
{
    return ListeningSection::query()->create([
        'listening_test_id' => $test->id,
        'section_number' => 1,
        'title' => 'Section 1',
        'start_question_number' => $start,
        'end_question_number' => $end,
        'total_questions' => $end - $start + 1,
        'is_active' => true,
    ]);
}

function listeningEvaluationQuestion(
    ListeningTest $test,
    ListeningSection $section,
    int $number,
    ListeningQuestionType $type,
    array $overrides = [],
): ListeningQuestion {
    $group = ListeningQuestionGroup::query()->firstOrCreate(
        [
            'listening_test_id' => $test->id,
            'listening_section_id' => $section->id,
            'start_question_number' => $number,
            'end_question_number' => $number,
        ],
        [
            'title' => "Group Q{$number}",
            'question_type' => $type,
            'total_questions' => 1,
            'display_order' => $number,
            'layout_type' => ListeningLayoutType::Default,
            'is_active' => true,
        ],
    );

    return ListeningQuestion::query()->create(array_merge([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'listening_question_group_id' => $group->id,
        'question_number' => $number,
        'question_type' => $type,
        'question_text' => "Question {$number}",
        'correct_answer' => [['value' => 'answer', 'type' => 'text']],
        'accepted_answers' => [],
        'answer_format' => ListeningAnswerFormat::Text,
        'marks' => 1,
        'is_active' => true,
        'display_order' => $number,
    ], $overrides));
}

function listeningEvaluationAttempt(ListeningTest $test, User $student, ListeningAttemptStatus $status = ListeningAttemptStatus::Submitted): ListeningAttempt
{
    return ListeningAttempt::query()->create([
        'user_id' => $student->id,
        'listening_test_id' => $test->id,
        'status' => $status,
        'started_at' => now()->subHour(),
        'submitted_at' => now(),
        'total_questions' => 40,
    ]);
}

function listeningEvaluationAnswerRow(
    ListeningAttempt $attempt,
    ListeningQuestion $question,
    ?array $studentAnswer,
): ListeningAttemptAnswer {
    return ListeningAttemptAnswer::query()->create([
        'listening_attempt_id' => $attempt->id,
        'listening_test_id' => $attempt->listening_test_id,
        'listening_question_id' => $question->id,
        'question_number' => $question->question_number,
        'student_answer' => $studentAnswer,
    ]);
}

it('matches exact text answers', function (): void {
    $test = listeningEvaluationTest();
    $section = listeningEvaluationSection($test);
    $question = listeningEvaluationQuestion($test, $section, 1, ListeningQuestionType::ShortAnswer, [
        'correct_answer' => [['value' => 'library', 'type' => 'text']],
    ]);
    $attempt = listeningEvaluationAttempt($test, listeningEvaluationStudent());
    $row = listeningEvaluationAnswerRow($attempt, $question, [['value' => 'library', 'type' => 'text']]);

    $result = app(TextAnswerEvaluator::class)->evaluate($row, $question);

    expect($result->isCorrect)->toBeTrue()
        ->and($result->matchReason)->toBe(ListeningMatchReason::EXACT_MATCH);
});

it('matches case-insensitive text answers', function (): void {
    $test = listeningEvaluationTest();
    $section = listeningEvaluationSection($test);
    $question = listeningEvaluationQuestion($test, $section, 1, ListeningQuestionType::ShortAnswer, [
        'correct_answer' => [['value' => 'Library', 'type' => 'text']],
        'case_sensitive' => false,
    ]);
    $attempt = listeningEvaluationAttempt($test, listeningEvaluationStudent());
    $row = listeningEvaluationAnswerRow($attempt, $question, [['value' => 'library', 'type' => 'text']]);

    $result = app(TextAnswerEvaluator::class)->evaluate($row, $question);

    expect($result->isCorrect)->toBeTrue()
        ->and($result->matchReason)->toBe(ListeningMatchReason::NORMALIZED_MATCH);
});

it('ignores punctuation when allowed', function (): void {
    $test = listeningEvaluationTest();
    $section = listeningEvaluationSection($test);
    $question = listeningEvaluationQuestion($test, $section, 1, ListeningQuestionType::SentenceCompletion, [
        'correct_answer' => [['value' => 'hello, world', 'type' => 'text']],
        'allow_punctuation_variation' => true,
    ]);
    $attempt = listeningEvaluationAttempt($test, listeningEvaluationStudent());
    $row = listeningEvaluationAnswerRow($attempt, $question, [['value' => 'hello world', 'type' => 'text']]);

    $result = app(CompletionAnswerEvaluator::class)->evaluate($row, $question);

    expect($result->isCorrect)->toBeTrue();
});

it('ignores articles when allowed', function (): void {
    $normalizer = app(ListeningAnswerNormalizationService::class);
    $test = listeningEvaluationTest();
    $section = listeningEvaluationSection($test);
    $question = listeningEvaluationQuestion($test, $section, 1, ListeningQuestionType::ShortAnswer, [
        'correct_answer' => [['value' => 'station', 'type' => 'text']],
        'allow_articles' => true,
    ]);
    $attempt = listeningEvaluationAttempt($test, listeningEvaluationStudent());
    $row = listeningEvaluationAnswerRow($attempt, $question, [['value' => 'the station', 'type' => 'text']]);

    $normalized = $normalizer->normalize([['value' => 'the station', 'type' => 'text']], $question, 'text');

    expect($normalized->primary())->toBe('station');
});

it('accepts plural variants when allowed', function (): void {
    $test = listeningEvaluationTest();
    $section = listeningEvaluationSection($test);
    $question = listeningEvaluationQuestion($test, $section, 1, ListeningQuestionType::ShortAnswer, [
        'correct_answer' => [['value' => 'book', 'type' => 'text']],
        'allow_plural' => true,
    ]);
    $attempt = listeningEvaluationAttempt($test, listeningEvaluationStudent());
    $row = listeningEvaluationAnswerRow($attempt, $question, [['value' => 'books', 'type' => 'text']]);

    $result = app(TextAnswerEvaluator::class)->evaluate($row, $question);

    expect($result->isCorrect)->toBeTrue()
        ->and($result->matchReason)->toBe(ListeningMatchReason::ACCEPTED_ANSWER_MATCH);
});

it('matches mcq letter answers', function (): void {
    $test = listeningEvaluationTest();
    $section = listeningEvaluationSection($test);
    $question = listeningEvaluationQuestion($test, $section, 1, ListeningQuestionType::MCQ, [
        'correct_answer' => [['value' => 'B', 'type' => 'letter']],
        'answer_format' => ListeningAnswerFormat::Letter,
    ]);
    $attempt = listeningEvaluationAttempt($test, listeningEvaluationStudent());
    $row = listeningEvaluationAnswerRow($attempt, $question, [['value' => 'b', 'type' => 'letter']]);

    $result = app(LetterAnswerEvaluator::class)->evaluate($row, $question);

    expect($result->isCorrect)->toBeTrue();
});

it('matches multiple answer full set', function (): void {
    $test = listeningEvaluationTest();
    $section = listeningEvaluationSection($test);
    $question = listeningEvaluationQuestion($test, $section, 1, ListeningQuestionType::MultipleAnswer, [
        'correct_answer' => [
            ['value' => 'A', 'type' => 'letter'],
            ['value' => 'C', 'type' => 'letter'],
        ],
        'answer_format' => ListeningAnswerFormat::Letter,
    ]);
    $attempt = listeningEvaluationAttempt($test, listeningEvaluationStudent());
    $row = listeningEvaluationAnswerRow($attempt, $question, [
        ['value' => 'C', 'type' => 'letter'],
        ['value' => 'A', 'type' => 'letter'],
    ]);

    $result = app(MultipleAnswerEvaluator::class)->evaluate($row, $question);

    expect($result->isCorrect)->toBeTrue();
});

it('fails multiple answer with extra wrong selections', function (): void {
    $test = listeningEvaluationTest();
    $section = listeningEvaluationSection($test);
    $question = listeningEvaluationQuestion($test, $section, 1, ListeningQuestionType::MultipleAnswer, [
        'correct_answer' => [
            ['value' => 'A', 'type' => 'letter'],
            ['value' => 'C', 'type' => 'letter'],
        ],
        'answer_format' => ListeningAnswerFormat::Letter,
    ]);
    $attempt = listeningEvaluationAttempt($test, listeningEvaluationStudent());
    $row = listeningEvaluationAnswerRow($attempt, $question, [
        ['value' => 'A', 'type' => 'letter'],
        ['value' => 'B', 'type' => 'letter'],
        ['value' => 'C', 'type' => 'letter'],
    ]);

    $result = app(MultipleAnswerEvaluator::class)->evaluate($row, $question);

    expect($result->isCorrect)->toBeFalse()
        ->and($result->matchReason)->toBe(ListeningMatchReason::INCORRECT_ANSWER);
});

it('matches matching answers', function (): void {
    $test = listeningEvaluationTest();
    $section = listeningEvaluationSection($test);
    $question = listeningEvaluationQuestion($test, $section, 17, ListeningQuestionType::Matching, [
        'correct_answer' => [['value' => 'B', 'type' => 'letter']],
        'answer_format' => ListeningAnswerFormat::Letter,
    ]);
    $attempt = listeningEvaluationAttempt($test, listeningEvaluationStudent());
    $row = listeningEvaluationAnswerRow($attempt, $question, [['value' => 'B', 'type' => 'letter']]);

    $result = app(MatchingAnswerEvaluator::class)->evaluate($row, $question);

    expect($result->isCorrect)->toBeTrue();
});

it('matches map labelling answers', function (): void {
    $test = listeningEvaluationTest();
    $section = listeningEvaluationSection($test);
    $question = listeningEvaluationQuestion($test, $section, 1, ListeningQuestionType::MapLabelling, [
        'correct_answer' => [['label' => 'C', 'value' => 'C', 'type' => 'map_label']],
        'answer_format' => ListeningAnswerFormat::MapLabel,
    ]);
    $attempt = listeningEvaluationAttempt($test, listeningEvaluationStudent());
    $row = listeningEvaluationAnswerRow($attempt, $question, [['label' => 'C', 'value' => 'C', 'type' => 'map_label']]);

    $result = app(LabellingAnswerEvaluator::class)->evaluate($row, $question);

    expect($result->isCorrect)->toBeTrue();
});

it('marks completion answers correct', function (): void {
    $test = listeningEvaluationTest();
    $section = listeningEvaluationSection($test);
    $question = listeningEvaluationQuestion($test, $section, 5, ListeningQuestionType::FormCompletion, [
        'correct_answer' => [['value' => 'Smith', 'type' => 'text']],
    ]);
    $attempt = listeningEvaluationAttempt($test, listeningEvaluationStudent());
    $row = listeningEvaluationAnswerRow($attempt, $question, [['value' => 'Smith', 'type' => 'text']]);

    $result = app(CompletionAnswerEvaluator::class)->evaluate($row, $question);

    expect($result->isCorrect)->toBeTrue();
});

it('marks word limit exceeded as incorrect', function (): void {
    $test = listeningEvaluationTest();
    $section = listeningEvaluationSection($test);
    $question = listeningEvaluationQuestion($test, $section, 1, ListeningQuestionType::ShortAnswer, [
        'correct_answer' => [['value' => 'one word', 'type' => 'text']],
        'word_limit' => 1,
    ]);
    $attempt = listeningEvaluationAttempt($test, listeningEvaluationStudent());
    $row = listeningEvaluationAnswerRow($attempt, $question, [['value' => 'two words', 'type' => 'text']]);

    $result = app(TextAnswerEvaluator::class)->evaluate($row, $question);

    expect($result->isCorrect)->toBeFalse()
        ->and($result->matchReason)->toBe(ListeningMatchReason::WORD_LIMIT_EXCEEDED);
});

it('accepts alternative answers', function (): void {
    $test = listeningEvaluationTest();
    $section = listeningEvaluationSection($test);
    $question = listeningEvaluationQuestion($test, $section, 1, ListeningQuestionType::ShortAnswer, [
        'correct_answer' => [['value' => 'lift', 'type' => 'text']],
        'accepted_answers' => [['value' => 'elevator', 'type' => 'text']],
    ]);
    $attempt = listeningEvaluationAttempt($test, listeningEvaluationStudent());
    $row = listeningEvaluationAnswerRow($attempt, $question, [['value' => 'elevator', 'type' => 'text']]);

    $result = app(TextAnswerEvaluator::class)->evaluate($row, $question);

    expect($result->isCorrect)->toBeTrue();
});

it('awards zero for unanswered questions', function (): void {
    $test = listeningEvaluationTest();
    $section = listeningEvaluationSection($test);
    $question = listeningEvaluationQuestion($test, $section, 1, ListeningQuestionType::ShortAnswer, [
        'correct_answer' => [['value' => 'answer', 'type' => 'text']],
    ]);
    $attempt = listeningEvaluationAttempt($test, listeningEvaluationStudent());
    $row = listeningEvaluationAnswerRow($attempt, $question, null);

    $result = app(TextAnswerEvaluator::class)->evaluate($row, $question);

    expect($result->marksAwarded)->toBe(0.0)
        ->and($result->matchStatus)->toBe(ListeningMatchStatus::Unanswered);
});

it('calculates raw score from attempt evaluation', function (): void {
    $test = listeningEvaluationTest();
    $section = listeningEvaluationSection($test);
    $q1 = listeningEvaluationQuestion($test, $section, 1, ListeningQuestionType::ShortAnswer, [
        'correct_answer' => [['value' => 'one', 'type' => 'text']],
    ]);
    $q2 = listeningEvaluationQuestion($test, $section, 2, ListeningQuestionType::ShortAnswer, [
        'correct_answer' => [['value' => 'two', 'type' => 'text']],
    ]);
    $attempt = listeningEvaluationAttempt($test, listeningEvaluationStudent());
    listeningEvaluationAnswerRow($attempt, $q1, [['value' => 'one', 'type' => 'text']]);
    listeningEvaluationAnswerRow($attempt, $q2, [['value' => 'wrong', 'type' => 'text']]);

    $result = app(ListeningAnswerEngineService::class)->evaluateAttempt($attempt);

    expect($result->rawScore)->toBe(1.0)
        ->and($result->totalCorrect)->toBe(1.0);
});

it('calculates band score from raw score', function (): void {
    $band = app(ListeningBandScoreService::class)->bandForRawScore(39);

    expect($band)->toBe(9.0);
});

it('stores answer key snapshots during evaluation', function (): void {
    $test = listeningEvaluationTest();
    $section = listeningEvaluationSection($test);
    $question = listeningEvaluationQuestion($test, $section, 1, ListeningQuestionType::ShortAnswer, [
        'correct_answer' => [['value' => 'snapshot', 'type' => 'text']],
        'accepted_answers' => [['value' => 'alt', 'type' => 'text']],
    ]);
    $attempt = listeningEvaluationAttempt($test, listeningEvaluationStudent());
    listeningEvaluationAnswerRow($attempt, $question, [['value' => 'snapshot', 'type' => 'text']]);

    app(ListeningAnswerEngineService::class)->evaluateAttempt($attempt);

    $answerEval = ListeningAttemptAnswerEvaluation::query()->first();

    expect($answerEval)->not->toBeNull()
        ->and($answerEval->correct_answer_snapshot)->toBe([['value' => 'snapshot', 'type' => 'text']])
        ->and($answerEval->accepted_answers_snapshot)->toBe([['value' => 'alt', 'type' => 'text']]);
});

it('dispatches evaluation job on submit when queue mode is enabled', function (): void {
    Queue::fake();
    config(['listening.answer_engine.mode' => 'queue']);

    $test = listeningEvaluationTest();
    $attempt = listeningEvaluationAttempt($test, listeningEvaluationStudent(), ListeningAttemptStatus::InProgress);
    $attempt->update(['status' => ListeningAttemptStatus::Submitted, 'submitted_at' => now()]);

    app(EvaluateListeningAttemptAction::class)->execute($attempt->refresh(), ['dispatch_only' => true]);

    Queue::assertPushed(EvaluateListeningAttemptJob::class);
});

it('cannot evaluate in-progress attempts', function (): void {
    $test = listeningEvaluationTest();
    $attempt = listeningEvaluationAttempt($test, listeningEvaluationStudent(), ListeningAttemptStatus::InProgress);

    expect(app(ListeningAnswerEngineService::class)->canEvaluate($attempt))->toBeFalse();
});

it('creates a new record on admin recheck', function (): void {
    $test = listeningEvaluationTest();
    $section = listeningEvaluationSection($test);
    $question = listeningEvaluationQuestion($test, $section, 1, ListeningQuestionType::ShortAnswer, [
        'correct_answer' => [['value' => 'yes', 'type' => 'text']],
    ]);
    $attempt = listeningEvaluationAttempt($test, listeningEvaluationStudent());
    listeningEvaluationAnswerRow($attempt, $question, [['value' => 'yes', 'type' => 'text']]);

    app(ListeningAnswerEngineService::class)->evaluateAttempt($attempt);
    $firstCount = ListeningAttemptEvaluation::query()->count();

    app(ReEvaluateListeningAttemptAction::class)->execute($attempt->refresh(), listeningEvaluationAdmin());

    expect(ListeningAttemptEvaluation::query()->count())->toBe($firstCount + 1)
        ->and(ListeningAttemptEvaluation::query()->latest('id')->first()?->evaluation_type)
        ->toBe(ListeningEvaluationType::AdminRecheck);
});

it('marks missing answer key as needs review', function (): void {
    $test = listeningEvaluationTest();
    $section = listeningEvaluationSection($test);
    $question = listeningEvaluationQuestion($test, $section, 1, ListeningQuestionType::ShortAnswer, [
        'correct_answer' => [],
        'accepted_answers' => [],
    ]);
    $attempt = listeningEvaluationAttempt($test, listeningEvaluationStudent());
    listeningEvaluationAnswerRow($attempt, $question, [['value' => 'anything', 'type' => 'text']]);

    $result = app(ListeningAnswerEngineService::class)->evaluateAttempt($attempt);

    expect($result->status)->toBe(ListeningEvaluationStatus::NeedsReview);
});

it('does not modify reading evaluation services', function (): void {
    expect(class_exists(\App\Services\Exam\ReadingEvaluationService::class))->toBeTrue()
        ->and(method_exists(\App\Services\Exam\ReadingEvaluationService::class, 'evaluateAttempt'))->toBeTrue();
});
