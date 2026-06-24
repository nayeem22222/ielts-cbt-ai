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

beforeEach(function (): void {
    seedRbac();
    test()->withoutVite();
});

function createShortAnswerBuilderContext(int $start = 27, int $end = 30): array
{
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'short-answer-builder-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    test()->actingAs($admin);

    $test = ReadingTest::query()->create([
        'title' => 'Short Answer Builder Test',
        'slug' => 'short-answer-builder-'.uniqid(),
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
        'instruction' => 'Answer the questions below.',
        'question_type' => OfficialReadingQuestionType::ShortAnswer->value,
        'start_question' => $start,
        'end_question' => $end,
        'sort_order' => 1,
        'status' => PassageStatus::Published->value,
    ]);

    return [$test, $passage, $group->refresh(), $admin];
}

it('renders short answer builder', function (): void {
    [, , $group] = createShortAnswerBuilderContext();

    $this->get(route('admin.reading-question-groups.short-answer-questions.index', $group))
        ->assertOk()
        ->assertSee('Short Answer Builder')
        ->assertSee('Add Short Answer Question');
});

it('creates updates and deletes short answer questions', function (): void {
    [, , $group] = createShortAnswerBuilderContext();

    $this->post(route('admin.reading-question-groups.short-answer-questions.store', $group), [
        'answer_rule' => ReadingCompletionAnswerRule::ThreeWords->value,
        'question_number' => 27,
        'prompt' => 'What is the main topic?',
        'correct_answer' => 'climate change',
        'alternative_answers' => ['global warming'],
        'case_sensitive' => false,
        'difficulty' => 'medium',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $question = ReadingQuestion::query()->where('group_id', $group->id)->firstOrFail();
    expect($question->prompt)->toBe('What is the main topic?');

    $answer = $question->correctAnswers()->first();
    expect($answer?->answer_json['word_limit'])->toBe('THREE_WORDS');

    $this->put(route('admin.reading-short-answer-questions.update', $question), [
        'correct_answer' => 'climate crisis',
        'alternative_answers' => ['climate change'],
        'case_sensitive' => true,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($question->fresh()->correctAnswers()->first()?->answer)->toBe('climate crisis');
    expect($question->fresh()->correctAnswers()->first()?->answer_json['case_sensitive'])->toBeTrue();

    $this->get(route('admin.reading-question-groups.short-answer-questions.edit', $group))
        ->assertOk()
        ->assertSee('What is the main topic?');

    $this->delete(route('admin.reading-short-answer-questions.destroy', $question))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(ReadingQuestion::query()->where('group_id', $group->id)->count())->toBe(0);
});

it('blocks duplicate short answer question numbers', function (): void {
    [, , $group] = createShortAnswerBuilderContext();

    $this->post(route('admin.reading-question-groups.short-answer-questions.store', $group), [
        'answer_rule' => ReadingCompletionAnswerRule::ThreeWords->value,
        'question_number' => 27,
        'prompt' => 'First question',
        'correct_answer' => 'alpha',
    ]);

    $this->post(route('admin.reading-question-groups.short-answer-questions.store', $group), [
        'answer_rule' => ReadingCompletionAnswerRule::ThreeWords->value,
        'question_number' => 27,
        'prompt' => 'Duplicate number',
        'correct_answer' => 'beta',
    ])->assertSessionHasErrors('question_number');
});

it('blocks short answer question numbers outside group range', function (): void {
    [, , $group] = createShortAnswerBuilderContext();

    $this->post(route('admin.reading-question-groups.short-answer-questions.store', $group), [
        'answer_rule' => ReadingCompletionAnswerRule::ThreeWords->value,
        'question_number' => 99,
        'prompt' => 'Out of range',
        'correct_answer' => 'alpha',
    ])->assertSessionHasErrors('question_number');
});

it('renders short answer preview route', function (): void {
    [, , $group] = createShortAnswerBuilderContext();

    $this->get(route('admin.reading-question-groups.short-answer-questions.preview', $group))
        ->assertOk()
        ->assertSee('Admin Preview')
        ->assertSee('Answer Rule');
});
