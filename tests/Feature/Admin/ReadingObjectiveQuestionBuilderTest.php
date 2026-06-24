<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\OfficialReadingQuestionType;
use App\Enums\Exam\PassageStatus;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingTest;

beforeEach(function (): void {
    seedRbac();
    test()->withoutVite();
});

function createObjectiveBuilderContext(OfficialReadingQuestionType $type, int $start = 1, int $end = 5): array
{
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'objective-builder-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    test()->actingAs($admin);

    $test = ReadingTest::query()->create([
        'title' => 'Objective Builder Test',
        'slug' => 'objective-builder-'.uniqid(),
        'exam_type' => ExamType::Academic,
        'duration_minutes' => 60,
        'status' => PublishStatus::Draft,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    test()->post(route('admin.reading-tests.passages.store', $test));
    $passage = ReadingPassage::query()->where('reading_test_id', $test->id)->latest('id')->firstOrFail();

    test()->put(route('admin.reading-tests.passages.update', [$test, $passage]), [
        'title' => 'Passage 1',
        'subtitle' => null,
        'instruction' => null,
        'start_question' => 1,
        'end_question' => 40,
        'content_html' => '<p>Passage</p>',
        'status' => PassageStatus::Published->value,
        'auto_paragraph_labels' => true,
    ]);

    test()->post(route('admin.reading-tests.passages.groups.store', [$test, $passage]));
    $group = ReadingQuestionGroup::query()->where('passage_id', $passage->id)->latest('id')->firstOrFail();

    test()->put(route('admin.reading-tests.passages.groups.update', [$test, $passage, $group]), [
        'title' => "Questions {$start}–{$end}",
        'instruction' => 'Objective instruction',
        'question_type' => $type->value,
        'start_question' => $start,
        'end_question' => $end,
        'sort_order' => 1,
        'status' => PassageStatus::Published->value,
    ]);

    return [$test, $passage, $group->refresh(), $admin];
}

it('renders true false not given objective builder', function (): void {
    [, , $group] = createObjectiveBuilderContext(OfficialReadingQuestionType::TrueFalseNotGiven);

    $this->get(route('admin.reading-question-groups.objective-questions.index', $group))
        ->assertOk()
        ->assertSee('Objective Question Builder')
        ->assertSee('True / False / Not Given');
});

it('creates edits and deletes true false not given questions', function (): void {
    [, , $group] = createObjectiveBuilderContext(OfficialReadingQuestionType::TrueFalseNotGiven);

    $this->post(route('admin.reading-question-groups.objective-questions.store', $group), [
        'question_number' => 1,
        'prompt' => 'The museum opened in 1901.',
        'correct_answer' => 'TRUE',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $question = ReadingQuestion::query()->where('group_id', $group->id)->firstOrFail();
    expect($question->correctAnswers()->first()?->answer)->toBe('TRUE');

    $this->put(route('admin.reading-objective-questions.update', $question), [
        'prompt' => 'Updated statement',
        'correct_answer' => 'FALSE',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($question->fresh()->correctAnswers()->first()?->answer)->toBe('FALSE');

    $this->post(route('admin.reading-objective-questions.duplicate', $question))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(ReadingQuestion::query()->where('group_id', $group->id)->count())->toBe(2);

    $this->delete(route('admin.reading-objective-questions.destroy', $question))
        ->assertRedirect()
        ->assertSessionHasNoErrors();
});

it('creates yes no not given questions', function (): void {
    [, , $group] = createObjectiveBuilderContext(OfficialReadingQuestionType::YesNoNotGiven);

    $this->post(route('admin.reading-question-groups.objective-questions.store', $group), [
        'question_number' => 1,
        'prompt' => 'The writer supports the proposal.',
        'correct_answer' => 'NO',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(ReadingQuestion::query()->first()->correctAnswers()->first()?->answer)->toBe('NO');
});

it('creates mcq single with four and six options', function (): void {
    [, , $group] = createObjectiveBuilderContext(OfficialReadingQuestionType::MultipleChoiceSingle, 1, 10);

    $fourOptions = collect(range(0, 3))->map(fn ($i) => [
        'option_key' => chr(65 + $i),
        'option_label' => 'Option '.chr(65 + $i),
    ])->all();

    $this->post(route('admin.reading-question-groups.objective-questions.store', $group), [
        'question_number' => 1,
        'prompt' => 'Which is correct?',
        'options' => $fourOptions,
        'correct_answer' => 'B',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $question = ReadingQuestion::query()->where('group_id', $group->id)->firstOrFail();
    expect($question->options)->toHaveCount(4);
    expect($question->correctAnswers()->first()?->answer)->toBe('B');

    $sixOptions = collect(range(0, 5))->map(fn ($i) => [
        'option_key' => chr(65 + $i),
        'option_label' => 'Choice '.chr(65 + $i),
    ])->all();

    $this->post(route('admin.reading-question-groups.objective-questions.store', $group), [
        'question_number' => 2,
        'prompt' => 'Second question',
        'options' => $sixOptions,
        'correct_answer' => 'F',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(ReadingQuestion::query()->where('group_id', $group->id)->count())->toBe(2);
});

it('creates mcq multiple with json correct answers', function (): void {
    [, , $group] = createObjectiveBuilderContext(OfficialReadingQuestionType::MultipleChoiceMultiple, 1, 10);

    $options = [
        ['option_key' => 'A', 'option_label' => 'Reason A'],
        ['option_key' => 'B', 'option_label' => 'Reason B'],
        ['option_key' => 'C', 'option_label' => 'Reason C'],
        ['option_key' => 'D', 'option_label' => 'Reason D'],
    ];

    $this->post(route('admin.reading-question-groups.objective-questions.store', $group), [
        'question_number' => 4,
        'prompt' => 'Choose TWO reasons',
        'options' => $options,
        'correct_answers' => ['A', 'C'],
    ])->assertRedirect()->assertSessionHasNoErrors();

    $answer = ReadingQuestion::query()->where('group_id', $group->id)->firstOrFail()->correctAnswers()->first();
    expect($answer?->answer_json)->toBe(['A', 'C']);
});

it('blocks duplicate question numbers and invalid answers', function (): void {
    [, , $group] = createObjectiveBuilderContext(OfficialReadingQuestionType::TrueFalseNotGiven);

    $this->post(route('admin.reading-question-groups.objective-questions.store', $group), [
        'question_number' => 1,
        'prompt' => 'First',
        'correct_answer' => 'TRUE',
    ]);

    $this->post(route('admin.reading-question-groups.objective-questions.store', $group), [
        'question_number' => 1,
        'prompt' => 'Duplicate',
        'correct_answer' => 'FALSE',
    ])->assertSessionHasErrors('question_number');

    $this->post(route('admin.reading-question-groups.objective-questions.store', $group), [
        'question_number' => 2,
        'prompt' => 'Invalid answer',
        'correct_answer' => 'MAYBE',
    ])->assertSessionHasErrors('correct_answer');
});

it('bulk imports true false questions', function (): void {
    [, , $group] = createObjectiveBuilderContext(OfficialReadingQuestionType::TrueFalseNotGiven);

    $this->post(route('admin.reading-question-groups.objective-questions.bulk-import', $group), [
        'import_text' => "1|The museum opened in 1901|TRUE\n2|The bridge is older than the castle|FALSE",
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(ReadingQuestion::query()->where('group_id', $group->id)->count())->toBe(2);
});

it('shows admin preview for objective questions', function (): void {
    [, , $group] = createObjectiveBuilderContext(OfficialReadingQuestionType::TrueFalseNotGiven);

    $this->post(route('admin.reading-question-groups.objective-questions.store', $group), [
        'question_number' => 1,
        'prompt' => 'Preview statement',
        'correct_answer' => 'NOT_GIVEN',
    ]);

    $this->get(route('admin.reading-question-groups.objective-questions.index', ['group' => $group, 'preview' => 1]))
        ->assertOk()
        ->assertSee('Preview statement');
});

it('rejects objective builder for matching groups', function (): void {
    [, , $group] = createObjectiveBuilderContext(OfficialReadingQuestionType::MatchingInformation);

    $this->get(route('admin.reading-question-groups.objective-questions.index', $group))
        ->assertNotFound();
});
