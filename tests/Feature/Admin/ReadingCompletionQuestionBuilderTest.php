<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\OfficialReadingQuestionType;
use App\Enums\Exam\PassageStatus;
use App\Enums\Exam\ReadingCompletionAnswerRule;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingTest;
use App\Support\Reading\CompletionPlaceholderParser;

beforeEach(function (): void {
    seedRbac();
    test()->withoutVite();
});

function createCompletionBuilderContext(OfficialReadingQuestionType $type, int $start = 27, int $end = 30): array
{
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'completion-builder-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    test()->actingAs($admin);

    $test = ReadingTest::query()->create([
        'title' => 'Completion Builder Test',
        'slug' => 'completion-builder-'.uniqid(),
        'exam_type' => ExamType::Academic,
        'duration_minutes' => 60,
        'status' => PublishStatus::Draft,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    test()->post(route('admin.reading-tests.passages.store', $test));
    $passage = ReadingPassage::query()->where('reading_test_id', $test->id)->latest('id')->firstOrFail();

    test()->put(route('admin.reading-tests.passages.update', [$test, $passage]), [
        'title' => 'Passage 3',
        'subtitle' => null,
        'instruction' => null,
        'start_question' => 27,
        'end_question' => 40,
        'content_html' => '<p>Passage</p>',
        'status' => PassageStatus::Published->value,
        'auto_paragraph_labels' => true,
    ]);

    test()->post(route('admin.reading-tests.passages.groups.store', [$test, $passage]));
    $group = ReadingQuestionGroup::query()->where('passage_id', $passage->id)->latest('id')->firstOrFail();

    test()->put(route('admin.reading-tests.passages.groups.update', [$test, $passage, $group]), [
        'title' => "Questions {$start}–{$end}",
        'instruction' => 'Completion instruction',
        'question_type' => $type->value,
        'start_question' => $start,
        'end_question' => $end,
        'sort_order' => 1,
        'status' => PassageStatus::Published->value,
    ]);

    return [$test, $passage, $group->refresh(), $admin];
}

it('renders summary completion builder', function (): void {
    [, , $group] = createCompletionBuilderContext(OfficialReadingQuestionType::SummaryCompletion);

    $this->get(route('admin.reading-question-groups.completion-questions.index', $group))
        ->assertOk()
        ->assertSee('Completion Question Builder')
        ->assertSee('Summary Completion');
});

it('detects blanks saves and refreshes summary questions', function (): void {
    [, , $group] = createCompletionBuilderContext(OfficialReadingQuestionType::SummaryCompletion);

    $template = '<p>The first {{27}} began in Europe. {{28}} later became important.</p>';

    $this->post(route('admin.reading-question-groups.completion-questions.template', $group), [
        'answer_rule' => ReadingCompletionAnswerRule::OneWordOnly->value,
        'template_html' => $template,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(ReadingQuestion::query()->where('group_id', $group->id)->count())->toBe(2);
    expect(ReadingQuestion::query()->where('question_number', 27)->exists())->toBeTrue();
    expect(ReadingQuestion::query()->where('question_number', 28)->exists())->toBeTrue();

    $question = ReadingQuestion::query()->where('question_number', 27)->firstOrFail();

    $this->put(route('admin.reading-completion-questions.update', $question), [
        'correct_answer' => 'industrial revolution',
        'alternative_answers' => ['Industrial Revolution'],
    ])->assertRedirect()->assertSessionHasNoErrors();

    $correct = $question->fresh()->correctAnswers()->first();
    expect($correct?->answer)->toBe('industrial revolution');
    expect($correct?->answer_json['answers'])->toBe(['industrial revolution', 'Industrial Revolution']);
    expect($correct?->answer_json['case_sensitive'])->toBeFalse();

    $this->get(route('admin.reading-question-groups.completion-questions.index', $group))
        ->assertOk()
        ->assertSee('industrial revolution');
});

it('creates edits and deletes sentence completion questions', function (): void {
    [, , $group] = createCompletionBuilderContext(OfficialReadingQuestionType::SentenceCompletion);

    $this->post(route('admin.reading-question-groups.completion-questions.store', $group), [
        'question_number' => 27,
        'prompt' => 'The first bridge was built in _________.',
        'correct_answer' => '1924',
        'alternative_answers' => ['nineteen twenty-four'],
    ])->assertRedirect()->assertSessionHasNoErrors();

    $question = ReadingQuestion::query()->where('group_id', $group->id)->firstOrFail();

    $this->put(route('admin.reading-completion-questions.update', $question), [
        'prompt' => 'The second bridge was built in _________.',
        'correct_answer' => '1930',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($question->fresh()->prompt)->toContain('second bridge');

    $this->delete(route('admin.reading-completion-questions.destroy', $question))
        ->assertRedirect()
        ->assertSessionHasNoErrors();
});

it('auto detects note completion blanks on save', function (): void {
    [, , $group] = createCompletionBuilderContext(OfficialReadingQuestionType::NoteCompletion, 33, 35);

    $this->post(route('admin.reading-question-groups.completion-questions.template', $group), [
        'answer_rule' => ReadingCompletionAnswerRule::OneWordOnly->value,
        'template_html' => '<p>Topic {{33}}</p><p>Detail {{34}}</p><p>Result {{35}}</p>',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(ReadingQuestion::query()->where('group_id', $group->id)->count())->toBe(3);
});

it('detects table completion blanks and saves template', function (): void {
    [, , $group] = createCompletionBuilderContext(OfficialReadingQuestionType::TableCompletion, 36, 37);

    $this->post(route('admin.reading-question-groups.completion-questions.template', $group), [
        'answer_rule' => ReadingCompletionAnswerRule::OneWordOnly->value,
        'template_html' => '<table><tr><td>France</td><td>{{36}}</td></tr><tr><td>Japan</td><td>{{37}}</td></tr></table>',
        'table_data' => [
            'rows' => [
                ['cells' => [['content' => 'France'], ['content' => '', 'is_blank' => true, 'blank_number' => 36]]],
                ['cells' => [['content' => 'Japan'], ['content' => '', 'is_blank' => true, 'blank_number' => 37]]],
            ],
        ],
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(ReadingQuestion::query()->where('group_id', $group->id)->count())->toBe(2);
});

it('detects flow chart completion blanks and saves template', function (): void {
    [, , $group] = createCompletionBuilderContext(OfficialReadingQuestionType::FlowChartCompletion, 38, 39);

    $this->post(route('admin.reading-question-groups.completion-questions.template', $group), [
        'answer_rule' => ReadingCompletionAnswerRule::OneWordOnly->value,
        'template_html' => '<div>{{38}}</div><div>{{39}}</div>',
        'flow_steps' => [
            ['text' => 'Collect water', 'is_blank' => false],
            ['text' => '', 'is_blank' => true, 'blank_number' => 38],
            ['text' => 'Dry materials', 'is_blank' => false],
            ['text' => '', 'is_blank' => true, 'blank_number' => 39],
        ],
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(ReadingQuestion::query()->where('group_id', $group->id)->pluck('question_number')->all())->toBe([38, 39]);
});

it('blocks duplicate placeholders in template validation', function (): void {
    [, , $group] = createCompletionBuilderContext(OfficialReadingQuestionType::SummaryCompletion);

    $this->post(route('admin.reading-question-groups.completion-questions.template', $group), [
        'answer_rule' => ReadingCompletionAnswerRule::OneWordOnly->value,
        'template_html' => '<p>{{27}} and again {{27}}</p>',
    ])->assertSessionHasErrors('template');
});

it('blocks duplicate question numbers in sentence completion', function (): void {
    [, , $group] = createCompletionBuilderContext(OfficialReadingQuestionType::SentenceCompletion);

    $this->post(route('admin.reading-question-groups.completion-questions.store', $group), [
        'question_number' => 27,
        'prompt' => 'Sentence one _________.',
        'correct_answer' => 'alpha',
    ])->assertRedirect();

    $this->post(route('admin.reading-question-groups.completion-questions.store', $group), [
        'question_number' => 27,
        'prompt' => 'Sentence two _________.',
        'correct_answer' => 'beta',
    ])->assertSessionHasErrors('question_number');
});

it('parses placeholder numbers from both formats', function (): void {
    $numbers = CompletionPlaceholderParser::extractNumbers('Start {{40}} then [Blank:41] end.');

    expect($numbers)->toBe([40, 41]);
});

it('parses spaced and labeled placeholders through parser facade', function (): void {
    $parsed = CompletionPlaceholderParser::parse('Reason {{ 29:cause }} and [blank:30]');

    expect($parsed)->toHaveCount(2)
        ->and($parsed[0]['question_number'])->toBe(29)
        ->and($parsed[0]['label'])->toBe('cause')
        ->and($parsed[1]['question_number'])->toBe(30);
});

it('parses placeholders wrapped in editor markup', function (): void {
    $parsed = CompletionPlaceholderParser::parse(
        '<p>encouraging <span class="completion-blank-token" data-completion-blank="5">{{5}}</span> as</p>'
        .'<p>In one process <strong>{{<span>6</span>}}</strong> terms</p>',
    );

    expect($parsed)->toHaveCount(2)
        ->and($parsed[0]['question_number'])->toBe(5)
        ->and($parsed[1]['question_number'])->toBe(6);
});

it('bulk imports sentence completion rows', function (): void {
    [, , $group] = createCompletionBuilderContext(OfficialReadingQuestionType::SentenceCompletion);

    $text = "27 | The first bridge was built in _________. | 1924 | 1924, nineteen twenty-four\n28 | The second bridge was built in _________. | 1930";

    $this->post(route('admin.reading-question-groups.completion-questions.bulk-import', $group), [
        'import_text' => $text,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(ReadingQuestion::query()->where('group_id', $group->id)->count())->toBe(2);
});

it('requires confirmation before removing linked questions from template', function (): void {
    [, , $group] = createCompletionBuilderContext(OfficialReadingQuestionType::SummaryCompletion);

    $this->post(route('admin.reading-question-groups.completion-questions.template', $group), [
        'answer_rule' => ReadingCompletionAnswerRule::OneWordOnly->value,
        'template_html' => '<p>{{27}} {{28}}</p>',
    ])->assertRedirect();

    $this->post(route('admin.reading-question-groups.completion-questions.template', $group), [
        'answer_rule' => ReadingCompletionAnswerRule::OneWordOnly->value,
        'template_html' => '<p>{{27}} only</p>',
    ])->assertSessionHasErrors('confirm_remove');

    $this->post(route('admin.reading-question-groups.completion-questions.template', $group), [
        'answer_rule' => ReadingCompletionAnswerRule::OneWordOnly->value,
        'template_html' => '<p>{{27}} only</p>',
        'confirm_remove' => true,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(ReadingQuestion::query()->where('group_id', $group->id)->count())->toBe(1);
});

it('renders completion preview route with answer rule', function (): void {
    [, , $group] = createCompletionBuilderContext(OfficialReadingQuestionType::SummaryCompletion);

    $this->get(route('admin.reading-question-groups.completion-questions.preview', $group))
        ->assertOk()
        ->assertSee('Admin Preview')
        ->assertSee('Answer Rule');
});

it('saves summary with three placeholders and persists after refresh', function (): void {
    [, , $group] = createCompletionBuilderContext(OfficialReadingQuestionType::SummaryCompletion, 27, 29);

    $this->post(route('admin.reading-question-groups.completion-questions.template', $group), [
        'answer_rule' => ReadingCompletionAnswerRule::OneWordOnly->value,
        'template_html' => '<p>{{27}} {{28}} {{29}}</p>',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(ReadingQuestion::query()->where('group_id', $group->id)->count())->toBe(3);

    $this->get(route('admin.reading-question-groups.completion-questions.edit', $group))
        ->assertOk()
        ->assertSee('Question 27')
        ->assertSee('Question 28')
        ->assertSee('Question 29');
});

it('saves table via dedicated table route', function (): void {
    [, , $group] = createCompletionBuilderContext(OfficialReadingQuestionType::TableCompletion, 36, 37);

    $this->post(route('admin.reading-question-groups.completion-questions.table', $group), [
        'answer_rule' => ReadingCompletionAnswerRule::OneWordOnly->value,
        'template_html' => '<table><tr><td>France</td><td>{{36}}</td></tr></table>',
        'table_data' => [
            'rows' => [
                ['cells' => [['content' => 'France'], ['content' => '', 'is_blank' => true, 'blank_number' => 36]]],
                ['cells' => [['content' => 'Japan'], ['content' => '', 'is_blank' => true, 'blank_number' => 37]]],
            ],
        ],
    ])->assertRedirect()->assertSessionHasNoErrors();

    $this->get(route('admin.reading-question-groups.completion-questions.edit', $group))
        ->assertOk()
        ->assertSee('Table Builder');

    expect(ReadingQuestion::query()->where('group_id', $group->id)->count())->toBe(2);
});

it('returns live detect json from template engine', function (): void {
    [, , $group] = createCompletionBuilderContext(OfficialReadingQuestionType::NoteCompletion, 33, 35);

    $this->postJson(route('admin.reading-question-groups.completion-questions.detect', $group), [
        'content' => '<ul><li>Topic {{33}}</li><li>Detail {{34}}</li></ul>',
    ])->assertOk()
        ->assertJsonPath('count', 2)
        ->assertJsonPath('valid', true);
});

it('updates answer via dedicated answer route with case sensitivity', function (): void {
    [, , $group] = createCompletionBuilderContext(OfficialReadingQuestionType::SummaryCompletion);

    $this->post(route('admin.reading-question-groups.completion-questions.template', $group), [
        'answer_rule' => ReadingCompletionAnswerRule::OneWordOnly->value,
        'template_html' => '<p>{{27}}</p>',
    ]);

    $question = ReadingQuestion::query()->where('group_id', $group->id)->firstOrFail();

    $this->put(route('admin.reading-completion-questions.answer', $question), [
        'correct_answer' => 'Paris',
        'alternative_answers' => ['paris'],
        'case_sensitive' => true,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $answer = $question->fresh()->correctAnswers()->first();
    expect($answer?->answer_json['case_sensitive'])->toBeTrue();
});
