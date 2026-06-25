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
use App\Models\ReadingQuestionOption;
use App\Models\ReadingTest;

beforeEach(function (): void {
    seedRbac();
    test()->withoutVite();
});

function createMatchingBuilderContext(OfficialReadingQuestionType $type, int $start = 1, int $end = 4): array
{
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'matching-builder-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    test()->actingAs($admin);

    $test = ReadingTest::query()->create([
        'title' => 'Matching Builder Test',
        'slug' => 'matching-builder-'.uniqid(),
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
        'instruction' => 'Matching instruction',
        'question_type' => $type->value,
        'start_question' => $start,
        'end_question' => $end,
        'sort_order' => 1,
        'status' => PassageStatus::Published->value,
    ]);

    $group->refresh();

    return [$test, $passage, $group, $admin];
}

function seedMatchingOptions(ReadingQuestionGroup $group, array $options): void
{
    foreach ($options as $index => $option) {
        if (is_array($option)) {
            [$key, $label] = $option;
        } else {
            $key = $option;
            $label = '';
        }

        test()->post(route('admin.reading-question-groups.matching.options.store', $group), [
            'option_key' => $key,
            'option_label' => $label,
            'sort_order' => $index + 1,
        ])->assertRedirect()->assertSessionHasNoErrors();
    }
}

it('renders matching question builder for matching information group', function (): void {
    [$test, $passage, $group] = createMatchingBuilderContext(OfficialReadingQuestionType::MatchingInformation);

    $this->get(route('admin.reading-question-groups.questions.index', $group))
        ->assertOk()
        ->assertSee('Matching Question Builder')
        ->assertSee('Matching Information')
        ->assertSee('Bulk Import');
});

it('creates matching information options and questions with correct answers', function (): void {
    [$test, $passage, $group] = createMatchingBuilderContext(OfficialReadingQuestionType::MatchingInformation, 1, 5);

    foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $letter) {
        $this->post(route('admin.reading-question-groups.matching.options.store', $group), [
            'option_key' => $letter,
            'option_label' => '',
        ])->assertRedirect()->assertSessionHasNoErrors();
    }

    $this->post(route('admin.reading-question-groups.matching.questions.store', $group), [
        'question_number' => 1,
        'prompt' => 'a reference to an appealing way of using dance',
        'correct_answer' => 'H',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $this->post(route('admin.reading-question-groups.matching.questions.store', $group), [
        'question_number' => 2,
        'prompt' => 'a contrast between past and present approaches',
        'correct_answer' => 'E',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $question = ReadingQuestion::query()->where('group_id', $group->id)->where('question_number', 1)->firstOrFail();

    expect(ReadingQuestionOption::query()->where('group_id', $group->id)->count())->toBe(8);
    expect($question->correctAnswers()->first()?->answer)->toBe('H');

    $this->get(route('admin.reading-question-groups.questions.index', ['group' => $group, 'preview' => 1]))
        ->assertOk()
        ->assertSee('a reference to an appealing way of using dance');
});

it('creates matching headings with roman keys and paragraph references', function (): void {
    [$test, $passage, $group] = createMatchingBuilderContext(OfficialReadingQuestionType::MatchingHeadings, 14, 20);

    $this->post(route('admin.reading-question-groups.matching.bulk-import', $group), [
        'options_text' => "i | The expansion of international tourism\nii | How local communities benefit\niii | The environmental cost",
        'questions_text' => "14 | Paragraph A | ii\n15 | Paragraph B | iii",
    ])->assertRedirect()->assertSessionHasNoErrors();

    $question = ReadingQuestion::query()->where('group_id', $group->id)->where('question_number', 14)->firstOrFail();

    expect(ReadingQuestionOption::query()->where('group_id', $group->id)->count())->toBe(3);
    expect($question->paragraph_reference)->toBe('A');
    expect($question->correctAnswers()->first()?->answer)->toBe('ii');
});

it('creates matching features statements and answers', function (): void {
    [$test, $passage, $group] = createMatchingBuilderContext(OfficialReadingQuestionType::MatchingFeatures, 21, 26);

    seedMatchingOptions($group, [
        ['A', 'Dr Johnson'],
        ['B', 'Professor Smith'],
        ['C', 'Maria Lopez'],
        ['D', 'Chen Wei'],
    ]);

    $this->post(route('admin.reading-question-groups.matching.questions.store', $group), [
        'question_number' => 21,
        'prompt' => 'developed a new method for measuring progress',
        'correct_answer' => 'B',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(ReadingQuestion::query()->where('group_id', $group->id)->count())->toBe(1);
});

it('creates matching people statements separately from matching features', function (): void {
    [$test, $passage, $group] = createMatchingBuilderContext(OfficialReadingQuestionType::MatchingPeople, 27, 30);

    expect($group->question_type)->toBe(OfficialReadingQuestionType::MatchingPeople);

    seedMatchingOptions($group, [['A', 'Alice Green'], ['B', 'Robert Hill'], ['C', 'Susan Parker']]);

    $this->post(route('admin.reading-question-groups.matching.questions.store', $group), [
        'question_number' => 27,
        'prompt' => 'said that old methods were unreliable',
        'correct_answer' => 'C',
    ])->assertRedirect()->assertSessionHasNoErrors();
});

it('creates matching sentence endings', function (): void {
    [$test, $passage, $group] = createMatchingBuilderContext(OfficialReadingQuestionType::MatchingSentenceEndings, 30, 35);

    $this->post(route('admin.reading-question-groups.matching.bulk-import', $group), [
        'options_text' => "A | the results were not reliable.\nB | a second trial was required.",
        'questions_text' => "30 | The first experiment showed that | B",
    ])->assertRedirect()->assertSessionHasNoErrors();

    $question = ReadingQuestion::query()->where('group_id', $group->id)->where('question_number', 30)->firstOrFail();
    expect($question->correctAnswers()->first()?->answer)->toBe('B');
});

it('blocks duplicate question numbers inside a group', function (): void {
    [$test, $passage, $group] = createMatchingBuilderContext(OfficialReadingQuestionType::MatchingInformation, 1, 4);
    seedMatchingOptions($group, ['A', 'B']);

    $this->post(route('admin.reading-question-groups.matching.questions.store', $group), [
        'question_number' => 1,
        'prompt' => 'First statement',
        'correct_answer' => 'A',
    ])->assertRedirect();

    $this->post(route('admin.reading-question-groups.matching.questions.store', $group), [
        'question_number' => 1,
        'prompt' => 'Duplicate number',
        'correct_answer' => 'B',
    ])->assertSessionHasErrors('question_number');
});

it('blocks question numbers outside group range', function (): void {
    [$test, $passage, $group] = createMatchingBuilderContext(OfficialReadingQuestionType::MatchingInformation, 1, 4);
    seedMatchingOptions($group, ['A', 'B']);

    $this->post(route('admin.reading-question-groups.matching.questions.store', $group), [
        'question_number' => 9,
        'prompt' => 'Out of range',
        'correct_answer' => 'A',
    ])->assertSessionHasErrors('question_number');
});

it('warns when deleting an option used as a correct answer', function (): void {
    [$test, $passage, $group] = createMatchingBuilderContext(OfficialReadingQuestionType::MatchingInformation, 1, 4);
    seedMatchingOptions($group, ['A', 'B']);

    $this->post(route('admin.reading-question-groups.matching.questions.store', $group), [
        'question_number' => 1,
        'prompt' => 'Statement',
        'correct_answer' => 'A',
    ]);

    $option = ReadingQuestionOption::query()->where('group_id', $group->id)->where('option_key', 'A')->firstOrFail();

    $this->delete(route('admin.reading-question-options.destroy', $option))
        ->assertSessionHasErrors('option');

    $this->delete(route('admin.reading-question-options.destroy', $option), ['confirm_delete' => 1])
        ->assertRedirect()
        ->assertSessionHasNoErrors();
});

it('rejects matching builder for non matching question types', function (): void {
    [$test, $passage, $group] = createMatchingBuilderContext(OfficialReadingQuestionType::SummaryCompletion, 5, 8);

    $this->get(route('admin.reading-question-groups.questions.index', $group))
        ->assertNotFound();
});

it('stores phrase-based passage references on matching questions', function (): void {
    [$test, $passage, $group] = createMatchingBuilderContext(OfficialReadingQuestionType::MatchingInformation, 1, 4);
    seedMatchingOptions($group, ['A', 'B', 'C']);

    $this->post(route('admin.reading-question-groups.matching.questions.store', $group), [
        'question_number' => 1,
        'prompt' => 'Statement one',
        'correct_answer' => 'A',
        'reference_type' => 'phrase',
        'reference_phrase' => 'concrete has shaped modern cities',
    ])->assertRedirect();

    $question = ReadingQuestion::query()->where('group_id', $group->id)->where('question_number', 1)->firstOrFail();

    expect($question->reference_type)->toBe('phrase')
        ->and($question->reference_phrase)->toBe('concrete has shaped modern cities');
});
