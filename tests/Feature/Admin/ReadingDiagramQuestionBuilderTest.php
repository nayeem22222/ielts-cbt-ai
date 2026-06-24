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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    seedRbac();
    test()->withoutVite();
    Storage::fake('uploads');
});

function createDiagramBuilderContext(int $start = 34, int $end = 36): array
{
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'diagram-builder-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    test()->actingAs($admin);

    $test = ReadingTest::query()->create([
        'title' => 'Diagram Builder Test',
        'slug' => 'diagram-builder-'.uniqid(),
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
        'instruction' => 'Label the diagram below.',
        'question_type' => OfficialReadingQuestionType::DiagramLabelCompletion->value,
        'start_question' => $start,
        'end_question' => $end,
        'sort_order' => 1,
        'status' => PassageStatus::Published->value,
    ]);

    return [$test, $passage, $group->refresh(), $admin];
}

it('renders diagram label builder', function (): void {
    [, , $group] = createDiagramBuilderContext();

    $this->get(route('admin.reading-question-groups.diagram-questions.index', $group))
        ->assertOk()
        ->assertSee('Diagram Label Builder')
        ->assertSee('Diagram Image');
});

it('uploads diagram image and serves it securely', function (): void {
    [, , $group] = createDiagramBuilderContext();

    $jpeg = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDAREAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAGfAP/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAQUCf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQMBAT8Bf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQIBAT8Bf//EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEABj8Cf//EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAT8hf//Z');
    $file = UploadedFile::fake()->createWithContent('diagram.jpg', $jpeg);

    $this->post(route('admin.reading-question-groups.diagram-questions.upload', $group), [
        'diagram_image' => $file,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $group = $group->fresh();
    $path = $group->settings['diagram_image'] ?? null;

    expect($path)->not->toBeNull();
    Storage::disk('uploads')->assertExists($path);

    $this->get(route('admin.reading-question-groups.diagram-questions.image', $group))
        ->assertOk();
});

it('saves diagram labels and persists after refresh', function (): void {
    [, , $group] = createDiagramBuilderContext();

    $this->post(route('admin.reading-question-groups.diagram-questions.labels', $group), [
        'answer_rule' => ReadingCompletionAnswerRule::OneWordOnly->value,
        'labels' => [
            [
                'question_number' => 34,
                'x' => 42.5,
                'y' => 61.2,
                'label' => 'pipe',
                'correct_answer' => 'valve',
                'alternative_answers' => ['the valve'],
                'difficulty' => 'medium',
            ],
            [
                'question_number' => 35,
                'x' => 20,
                'y' => 30,
                'label' => 'tank',
                'correct_answer' => 'reservoir',
                'difficulty' => 'easy',
            ],
        ],
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(ReadingQuestion::query()->where('group_id', $group->id)->count())->toBe(2);

    $question = ReadingQuestion::query()->where('question_number', 34)->firstOrFail();
    expect($question->metadata['x'])->toBe(42.5);
    expect($question->metadata['label'])->toBe('pipe');

    $answer = $question->correctAnswers()->first();
    expect($answer?->answer)->toBe('valve');
    expect($answer?->answer_json['answers'])->toContain('the valve');

    $this->get(route('admin.reading-question-groups.diagram-questions.edit', $group))
        ->assertOk()
        ->assertSee('pipe')
        ->assertSee('valve')
        ->assertSee('reservoir');
});

it('deletes a saved diagram label', function (): void {
    [, , $group] = createDiagramBuilderContext();

    $this->post(route('admin.reading-question-groups.diagram-questions.labels', $group), [
        'answer_rule' => ReadingCompletionAnswerRule::OneWordOnly->value,
        'labels' => [
            ['question_number' => 34, 'x' => 10, 'y' => 10, 'correct_answer' => 'valve'],
            ['question_number' => 35, 'x' => 20, 'y' => 20, 'correct_answer' => 'pump'],
        ],
    ]);

    $question = ReadingQuestion::query()->where('question_number', 35)->firstOrFail();

    $this->delete(route('admin.reading-diagram-questions.destroy', $question))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(ReadingQuestion::query()->where('group_id', $group->id)->count())->toBe(1);
    expect(collect($group->fresh()->settings['labels'] ?? [])->pluck('question_number')->all())->toBe([34]);
});

it('blocks duplicate diagram label numbers', function (): void {
    [, , $group] = createDiagramBuilderContext();

    $this->post(route('admin.reading-question-groups.diagram-questions.labels', $group), [
        'answer_rule' => ReadingCompletionAnswerRule::OneWordOnly->value,
        'labels' => [
            ['question_number' => 34, 'x' => 10, 'y' => 10],
            ['question_number' => 34, 'x' => 20, 'y' => 20],
        ],
    ])->assertSessionHasErrors('labels');
});

it('blocks diagram labels outside group range', function (): void {
    [, , $group] = createDiagramBuilderContext();

    $this->post(route('admin.reading-question-groups.diagram-questions.labels', $group), [
        'answer_rule' => ReadingCompletionAnswerRule::OneWordOnly->value,
        'labels' => [
            ['question_number' => 99, 'x' => 10, 'y' => 10],
        ],
    ])->assertSessionHasErrors('labels');
});

it('blocks invalid diagram image type', function (): void {
    [, , $group] = createDiagramBuilderContext();

    $file = UploadedFile::fake()->create('diagram.pdf', 100, 'application/pdf');

    $this->post(route('admin.reading-question-groups.diagram-questions.upload', $group), [
        'diagram_image' => $file,
    ])->assertSessionHasErrors('diagram_image');
});

it('requires confirmation before removing saved diagram labels', function (): void {
    [, , $group] = createDiagramBuilderContext();

    $this->post(route('admin.reading-question-groups.diagram-questions.labels', $group), [
        'answer_rule' => ReadingCompletionAnswerRule::OneWordOnly->value,
        'labels' => [
            ['question_number' => 34, 'x' => 10, 'y' => 10, 'correct_answer' => 'valve'],
            ['question_number' => 35, 'x' => 20, 'y' => 20, 'correct_answer' => 'pump'],
        ],
    ]);

    $this->post(route('admin.reading-question-groups.diagram-questions.labels', $group), [
        'answer_rule' => ReadingCompletionAnswerRule::OneWordOnly->value,
        'labels' => [
            ['question_number' => 34, 'x' => 10, 'y' => 10, 'correct_answer' => 'valve'],
        ],
    ])->assertSessionHasErrors('confirm_remove');
});

it('renders diagram preview route', function (): void {
    [, , $group] = createDiagramBuilderContext();

    $this->get(route('admin.reading-question-groups.diagram-questions.preview', $group))
        ->assertOk()
        ->assertSee('Admin Preview')
        ->assertSee('Answer Rule');
});
