<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\OfficialReadingQuestionType;
use App\Enums\Exam\PassageStatus;
use App\Models\ReadingCorrectAnswer;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingQuestionOption;
use App\Models\ReadingTest;
use App\Services\Admin\Exam\ReadingTestValidationService;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    seedRbac();
    test()->withoutVite();
    Storage::fake('uploads');
});

function createValidationAdmin(): mixed
{
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'validation-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    test()->actingAs($admin);

    return $admin;
}

function createValidationTest(): ReadingTest
{
    createValidationAdmin();

    return ReadingTest::query()->create([
        'title' => 'Validation Test',
        'slug' => 'validation-'.uniqid(),
        'exam_type' => ExamType::Academic,
        'duration_minutes' => 60,
        'status' => PublishStatus::Draft,
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
    ]);
}

function addValidationPassage(ReadingTest $test, array $overrides = []): ReadingPassage
{
    test()->post(route('admin.reading-tests.passages.store', $test));

    $passage = ReadingPassage::query()->where('reading_test_id', $test->id)->latest('id')->firstOrFail();

    test()->put(route('admin.reading-tests.passages.update', [$test, $passage]), array_merge([
        'title' => 'Passage 1',
        'subtitle' => null,
        'instruction' => 'Read and answer.',
        'start_question' => 1,
        'end_question' => 13,
        'content_html' => '<p>Passage content.</p>',
        'status' => PassageStatus::Draft->value,
        'auto_paragraph_labels' => true,
    ], $overrides));

    return $passage->fresh();
}

function addValidationGroup(ReadingTest $test, ReadingPassage $passage, OfficialReadingQuestionType $type, int $start, int $end, array $overrides = []): ReadingQuestionGroup
{
    if ($end <= $start) {
        return $passage->groups()->create(array_merge([
            'title' => "Questions {$start}–{$end}",
            'instruction' => 'Answer the questions.',
            'question_type' => $type,
            'start_question' => $start,
            'end_question' => $end,
            'sort_order' => 1,
            'status' => PassageStatus::Draft,
            'settings' => [],
        ], $overrides));
    }

    test()->post(route('admin.reading-tests.passages.groups.store', [$test, $passage]));
    $group = ReadingQuestionGroup::query()->where('passage_id', $passage->id)->latest('id')->firstOrFail();

    test()->put(route('admin.reading-tests.passages.groups.update', [$test, $passage, $group]), array_merge([
        'title' => "Questions {$start}–{$end}",
        'instruction' => 'Answer the questions.',
        'question_type' => $type->value,
        'start_question' => $start,
        'end_question' => $end,
        'sort_order' => 1,
        'status' => PassageStatus::Draft->value,
    ], $overrides))->assertRedirect()->assertSessionHasNoErrors();

    return $group->fresh();
}

function seedMatchingQuestion(ReadingQuestionGroup $group, int $number, string $prompt, string $answerKey): ReadingQuestion
{
    $group->groupOptions()->firstOrCreate(
        ['option_key' => $answerKey],
        ['option_label' => 'Option '.$answerKey, 'sort_order' => 1],
    );

    /** @var ReadingQuestion $question */
    $question = $group->questions()->create([
        'question_number' => $number,
        'prompt' => $prompt,
        'sort_order' => $number,
        'marks' => 1,
        'difficulty' => 'medium',
    ]);

    $question->correctAnswers()->create([
        'answer' => $answerKey,
        'answer_json' => null,
        'matching_key' => null,
    ]);

    return $question;
}

it('blocks publish when test has no passages', function (): void {
    $test = createValidationTest();

    $this->post(route('admin.reading-tests.publish', $test))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($test->fresh()->status)->toBe(PublishStatus::Draft);
});

it('blocks publish when passage has no question groups', function (): void {
    $test = createValidationTest();
    addValidationPassage($test);

    $this->post(route('admin.reading-tests.publish', $test))
        ->assertRedirect()
        ->assertSessionHas('error');
});

it('blocks publish when group expects four questions but only three exist', function (): void {
    $test = createValidationTest();
    $passage = addValidationPassage($test, ['start_question' => 1, 'end_question' => 4]);
    $group = addValidationGroup($test, $passage, OfficialReadingQuestionType::MatchingInformation, 1, 4);

    foreach ([1, 2, 3] as $number) {
        seedMatchingQuestion($group, $number, "Statement {$number}", 'A');
    }

    $result = app(ReadingTestValidationService::class)->validatePublishReady($test->fresh());

    expect($result['is_valid'])->toBeFalse()
        ->and(collect($result['errors'])->pluck('type'))->toContain('group_question_count_mismatch');
});

it('blocks publish for duplicate question numbers', function (): void {
    $test = createValidationTest();
    $passage = addValidationPassage($test, ['start_question' => 1, 'end_question' => 2]);
    $group = addValidationGroup($test, $passage, OfficialReadingQuestionType::ShortAnswer, 1, 2);

    foreach ([1, 1] as $index => $number) {
        $group->questions()->create([
            'question_number' => $number,
            'prompt' => 'Question '.$index,
            'sort_order' => $index + 1,
            'marks' => 1,
            'difficulty' => 'medium',
        ]);
    }

    $result = app(ReadingTestValidationService::class)->validatePublishReady($test->fresh());

    expect($result['is_valid'])->toBeFalse()
        ->and(collect($result['errors'])->pluck('type'))->toContain('question_number_duplicate');
});

it('blocks publish when correct answer is missing', function (): void {
    $test = createValidationTest();
    $passage = addValidationPassage($test, ['start_question' => 1, 'end_question' => 1]);
    $group = addValidationGroup($test, $passage, OfficialReadingQuestionType::ShortAnswer, 1, 1);

    $group->questions()->create([
        'question_number' => 1,
        'prompt' => 'What is the topic?',
        'sort_order' => 1,
        'marks' => 1,
        'difficulty' => 'medium',
    ]);

    $result = app(ReadingTestValidationService::class)->validatePublishReady($test->fresh());

    expect($result['is_valid'])->toBeFalse()
        ->and(collect($result['errors'])->pluck('type'))->toContain('question_answer_missing');
});

it('blocks publish when mcq answer is not in options', function (): void {
    $test = createValidationTest();
    $passage = addValidationPassage($test, ['start_question' => 1, 'end_question' => 1]);
    $group = addValidationGroup($test, $passage, OfficialReadingQuestionType::MultipleChoiceSingle, 1, 1);

    $question = $group->questions()->create([
        'question_number' => 1,
        'prompt' => 'Choose one.',
        'sort_order' => 1,
        'marks' => 1,
        'difficulty' => 'medium',
    ]);

    $question->options()->create(['option_key' => 'A', 'option_label' => 'Alpha', 'sort_order' => 1]);
    $question->correctAnswers()->create(['answer' => 'Z', 'answer_json' => null, 'matching_key' => null]);

    $result = app(ReadingTestValidationService::class)->validateOptions($group->fresh());

    expect($result['is_valid'])->toBeFalse()
        ->and(collect($result['errors'])->pluck('type'))->toContain('mcq_answer_option_missing');
});

it('blocks completion group with duplicate placeholders', function (): void {
    $test = createValidationTest();
    $passage = addValidationPassage($test, ['start_question' => 27, 'end_question' => 28]);
    $group = addValidationGroup($test, $passage, OfficialReadingQuestionType::SummaryCompletion, 27, 28);
    $group->forceFill([
        'settings' => [
            'answer_rule' => 'one_word_only',
            'template_html' => '<p>{{27}} and {{27}}</p>',
        ],
    ])->save();

    $result = app(ReadingTestValidationService::class)->validateQuestions($test->fresh());

    expect($result['is_valid'])->toBeFalse()
        ->and(collect($result['errors'])->pluck('type'))->toContain('completion_template_invalid');
});

it('blocks diagram group with invalid label coordinates', function (): void {
    $test = createValidationTest();
    $passage = addValidationPassage($test, ['start_question' => 31, 'end_question' => 32]);
    $group = addValidationGroup($test, $passage, OfficialReadingQuestionType::DiagramLabelCompletion, 31, 32);

    Storage::disk('uploads')->put('reading/diagrams/'.$group->id.'/diagram.jpg', 'image');
    $group->forceFill([
        'settings' => [
            'diagram_image' => 'reading/diagrams/'.$group->id.'/diagram.jpg',
            'labels' => [
                ['question_number' => 31, 'x' => 120, 'y' => 10, 'label' => 'pipe'],
            ],
        ],
    ])->save();

    $result = app(ReadingTestValidationService::class)->validateQuestions($test->fresh());

    expect($result['is_valid'])->toBeFalse()
        ->and(collect($result['errors'])->pluck('type'))->toContain('diagram_label_coordinates_invalid');
});

it('allows publish for a valid matching group test', function (): void {
    $test = createValidationTest();
    $passage = addValidationPassage($test, ['start_question' => 1, 'end_question' => 2]);
    $group = addValidationGroup($test, $passage, OfficialReadingQuestionType::MatchingInformation, 1, 2);

    seedMatchingQuestion($group, 1, 'Statement one', 'A');
    seedMatchingQuestion($group, 2, 'Statement two', 'B');

    $result = app(ReadingTestValidationService::class)->validatePublishReady($test->fresh());

    expect($result['is_valid'])->toBeTrue();

    $this->post(route('admin.reading-tests.publish', $test))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($test->fresh()->status)->toBe(PublishStatus::Published);
});

it('renders validation dashboard and full preview routes', function (): void {
    $test = createValidationTest();
    $passage = addValidationPassage($test, ['start_question' => 1, 'end_question' => 1]);
    $group = addValidationGroup($test, $passage, OfficialReadingQuestionType::TrueFalseNotGiven, 1, 1);

    $question = $group->questions()->create([
        'question_number' => 1,
        'prompt' => 'The statement is true.',
        'sort_order' => 1,
        'marks' => 1,
        'difficulty' => 'medium',
    ]);

    $question->correctAnswers()->create(['answer' => 'TRUE', 'answer_json' => null, 'matching_key' => null]);

    $this->get(route('admin.reading-tests.validation', $test))
        ->assertOk()
        ->assertSee('Validation Summary');

    $this->post(route('admin.reading-tests.validate', $test))
        ->assertRedirect(route('admin.reading-tests.validation', $test));

    $this->get(route('admin.reading-tests.preview-full', $test))
        ->assertOk()
        ->assertSee('Full Preview')
        ->assertSee('Admin Preview — True / False / Not Given');
});

it('shows validation panel on builder page', function (): void {
    $test = createValidationTest();
    addValidationPassage($test);

    $this->get(route('admin.reading-tests.builder', $test))
        ->assertOk()
        ->assertSee('Validation Summary')
        ->assertSee('Publish Readiness');
});
