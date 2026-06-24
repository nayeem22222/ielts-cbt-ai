<?php

declare(strict_types=1);

use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\OfficialReadingQuestionType;
use App\Enums\Exam\PassageStatus;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingTest;
use App\Services\Admin\Exam\ReadingCompletionTemplateService;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->template = app(ReadingCompletionTemplateService::class);
});

function makeCompletionTemplateGroup(int $start = 27, int $end = 30): ReadingQuestionGroup
{
    $test = ReadingTest::query()->create([
        'title' => 'Template Engine Test',
        'slug' => 'template-engine-'.uniqid(),
        'exam_type' => ExamType::Academic,
        'duration_minutes' => 60,
        'status' => PublishStatus::Draft,
    ]);

    $passage = ReadingPassage::query()->create([
        'reading_test_id' => $test->id,
        'part_number' => 3,
        'title' => 'Passage 3',
        'sort_order' => 1,
        'start_question' => 27,
        'end_question' => 40,
    ]);

    return ReadingQuestionGroup::query()->create([
        'passage_id' => $passage->id,
        'title' => "Questions {$start}–{$end}",
        'instruction' => 'Complete the summary.',
        'question_type' => OfficialReadingQuestionType::SummaryCompletion,
        'start_question' => $start,
        'end_question' => $end,
        'sort_order' => 1,
        'status' => PassageStatus::Published,
        'settings' => [],
    ]);
}

it('parses brace placeholder {{27}}', function (): void {
    $parsed = $this->template->parseTemplate('The first {{27}} began in Europe.');

    expect($parsed)->toHaveCount(1)
        ->and($parsed[0]['question_number'])->toBe(27)
        ->and($parsed[0]['raw_placeholder'])->toBe('{{27}}')
        ->and($parsed[0]['label'])->toBeNull();
});

it('parses spaced brace placeholder {{ 27 }}', function (): void {
    $parsed = $this->template->parseTemplate('The first {{ 27 }} began in Europe.');

    expect($parsed[0]['question_number'])->toBe(27)
        ->and($parsed[0]['raw_placeholder'])->toBe('{{ 27 }}');
});

it('parses bracket placeholder [Blank:27]', function (): void {
    $parsed = $this->template->parseTemplate('Population increased in [Blank:27].');

    expect($parsed[0]['question_number'])->toBe(27)
        ->and($parsed[0]['raw_placeholder'])->toBe('[Blank:27]');
});

it('parses lowercase bracket placeholder [blank:27]', function (): void {
    $parsed = $this->template->parseTemplate('Population increased in [blank:27].');

    expect($parsed[0]['question_number'])->toBe(27);
});

it('parses labeled brace placeholder {{27:cause}}', function (): void {
    $parsed = $this->template->parseTemplate('The main reason was {{27:cause}}.');

    expect($parsed[0]['question_number'])->toBe(27)
        ->and($parsed[0]['label'])->toBe('cause')
        ->and($parsed[0]['before_text'])->toContain('The main reason was');
});

it('parses labeled bracket placeholder [Blank:28:population]', function (): void {
    $parsed = $this->template->parseTemplate('Population increased in [Blank:28:population].');

    expect($parsed[0]['question_number'])->toBe(28)
        ->and($parsed[0]['label'])->toBe('population');
});

it('blocks duplicate placeholders', function (): void {
    $group = makeCompletionTemplateGroup();
    $placeholders = $this->template->parseTemplate('{{27}} and again {{27}}');

    expect(fn () => $this->template->validatePlaceholders($group, $placeholders))
        ->toThrow(ValidationException::class);
});

it('blocks placeholders outside group range', function (): void {
    $group = makeCompletionTemplateGroup(27, 30);
    $placeholders = $this->template->parseTemplate('Out of range {{41}}');

    expect(fn () => $this->template->validatePlaceholders($group, $placeholders))
        ->toThrow(ValidationException::class);
});

it('detects removed placeholder candidates without deleting', function (): void {
    $group = makeCompletionTemplateGroup();

    $this->template->syncQuestions($group, '<p>{{27}} {{28}}</p>');
    expect(ReadingQuestion::query()->where('group_id', $group->id)->count())->toBe(2);

    $result = $this->template->syncQuestions($group, '<p>{{27}} only</p>');

    expect($result['removed_candidates'])->toBe([28])
        ->and(ReadingQuestion::query()->where('group_id', $group->id)->count())->toBe(2);
});

it('saves structured answer json with alternatives', function (): void {
    $group = makeCompletionTemplateGroup();
    $result = $this->template->syncQuestions($group, '<p>{{27}}</p>', [
        27 => [
            'answers' => ['industrial revolution', 'Industrial Revolution'],
            'case_sensitive' => false,
            'word_limit' => 'ONE_WORD_ONLY',
            'regex' => null,
        ],
    ]);

    $question = $result['created'][0];
    $answer = $question->correctAnswers()->first();

    expect($answer?->answer)->toBe('industrial revolution')
        ->and($answer?->answer_json)->toMatchArray([
            'answers' => ['industrial revolution', 'Industrial Revolution'],
            'case_sensitive' => false,
            'word_limit' => 'ONE_WORD_ONLY',
            'regex' => null,
        ]);
});

it('supports legacy correct_answer and alternative_answers input', function (): void {
    $group = makeCompletionTemplateGroup();
    $result = $this->template->syncQuestions($group, '<p>{{27}}</p>');

    $this->template->syncCorrectAnswers($result['created'][0], [
        'correct_answer' => 'Paris',
        'alternative_answers' => ['paris'],
        'case_sensitive' => false,
        'word_limit' => 'one_word_only',
    ]);

    $answer = $result['created'][0]->fresh()->correctAnswers()->first();

    expect($answer?->answer_json['answers'])->toBe(['Paris', 'paris']);
});

it('normalizes answers case-insensitively by default', function (): void {
    expect($this->template->normalizeAnswer('Industrial  Revolution'))->toBe('industrial revolution')
        ->and($this->template->normalizeAnswer('Industrial Revolution', true))->toBe('Industrial Revolution');
});

it('blocks broken placeholder syntax', function (): void {
    expect(fn () => $this->template->parseTemplate('Broken {{abc}} token'))
        ->toThrow(ValidationException::class);
});

it('blocks empty template during sync', function (): void {
    $group = makeCompletionTemplateGroup();

    expect(fn () => $this->template->syncQuestions($group, '   '))
        ->toThrow(ValidationException::class);
});

it('returns created updated and unchanged buckets on sync', function (): void {
    $group = makeCompletionTemplateGroup();

    $first = $this->template->syncQuestions($group, '<p>{{27}} {{28}}</p>');
    expect($first['created'])->toHaveCount(2);

    $second = $this->template->syncQuestions($group, '<p>{{27}} {{28}}</p>');
    expect($second['created'])->toHaveCount(0)
        ->and($second['unchanged'])->toBe([27, 28]);
});
