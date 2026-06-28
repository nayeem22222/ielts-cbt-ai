<?php

declare(strict_types=1);

use App\Enums\Listening\ListeningQuestionType;
use App\Services\Listening\QuestionTypes\ListeningQuestionTypeRegistry;

it('validates mcq options', function (): void {
    $service = app(ListeningQuestionTypeRegistry::class)->serviceFor(ListeningQuestionType::MCQ);

    expect($service->validatePayload([
        'options' => [['key' => 'A', 'text' => 'One']],
    ]))->not->toBeEmpty();

    expect($service->validatePayload([
        'options' => [
            ['key' => 'A', 'text' => 'One'],
            ['key' => 'B', 'text' => 'Two'],
        ],
    ]))->toBeEmpty();
});

it('validates multiple answer required count', function (): void {
    $service = app(ListeningQuestionTypeRegistry::class)->serviceFor(ListeningQuestionType::MultipleAnswer);

    $errors = $service->validatePayload([
        'options' => [
            ['key' => 'A', 'text' => 'A'],
            ['key' => 'B', 'text' => 'B'],
            ['key' => 'C', 'text' => 'C'],
        ],
        'settings' => ['required_answers' => 0],
    ]);

    expect($errors)->toContain('Required answers count must be at least 1.');
});

it('validates matching choices without requiring separate matching items', function (): void {
    $service = app(ListeningQuestionTypeRegistry::class)->serviceFor(ListeningQuestionType::Matching);

    expect($service->validatePayload(['options' => []]))->toBeEmpty();

    expect($service->validatePayload([
        'options' => [
            'choices' => [['key' => 'A', 'text' => 'One']],
            'items' => [],
        ],
    ]))->toBeEmpty();

    expect($service->validatePayload([
        'options' => [
            'choices' => [],
            'items' => [],
        ],
    ]))->toContain('At least one matching option is required.');
});

it('validates map labelling requires image and points', function (): void {
    $service = app(ListeningQuestionTypeRegistry::class)->serviceFor(ListeningQuestionType::MapLabelling);

    $errors = $service->validatePayload(['options' => ['labels' => [['key' => 'A', 'text' => 'X']], 'points' => []]]);

    expect($errors)->not->toBeEmpty();
});

it('validates plan labelling coordinates', function (): void {
    $service = app(ListeningQuestionTypeRegistry::class)->serviceFor(ListeningQuestionType::PlanLabelling);

    $errors = $service->validatePayload([
        'image_path' => '/maps/plan.png',
        'options' => [
            'labels' => [['key' => 'A', 'text' => 'Room']],
            'points' => [['number' => 1, 'x' => 150, 'y' => 50]],
        ],
    ]);

    expect($errors)->not->toBeEmpty();
});

it('validates diagram labelling labels', function (): void {
    $service = app(ListeningQuestionTypeRegistry::class)->serviceFor(ListeningQuestionType::DiagramLabelling);

    expect($service->validatePayload([
        'image_path' => '/diagram.png',
        'options' => [
            'labels' => [],
            'points' => [['number' => 1, 'x' => 40, 'y' => 30]],
        ],
    ]))->not->toBeEmpty();
});

it('validates form completion blanks', function (): void {
    $service = app(ListeningQuestionTypeRegistry::class)->serviceFor(ListeningQuestionType::FormCompletion);
    $group = new \App\Models\Listening\ListeningQuestionGroup([
        'start_question_number' => 1,
        'end_question_number' => 2,
    ]);

    expect($service->validatePayload(['content' => '', 'settings' => ['word_limit' => 2]], $group))->not->toBeEmpty();
});

it('validates note completion blanks', function (): void {
    $service = app(ListeningQuestionTypeRegistry::class)->serviceFor(ListeningQuestionType::NoteCompletion);
    $group = new \App\Models\Listening\ListeningQuestionGroup(['start_question_number' => 11, 'end_question_number' => 13]);

    $errors = $service->validatePayload([
        'content' => "Note [blank:11]\nPrice [blank:12]",
        'settings' => ['word_limit' => 2],
    ], $group);

    expect($errors)->toBeEmpty();
});

it('validates table completion rows', function (): void {
    $service = app(ListeningQuestionTypeRegistry::class)->serviceFor(ListeningQuestionType::TableCompletion);

    expect($service->validatePayload(['settings' => ['columns' => [], 'rows' => []]]))->not->toBeEmpty();
});

it('validates flowchart completion steps', function (): void {
    $service = app(ListeningQuestionTypeRegistry::class)->serviceFor(ListeningQuestionType::FlowchartCompletion);

    expect($service->validatePayload(['settings' => ['steps' => [['order' => 1, 'text' => 'Only text']]]]))->not->toBeEmpty();
});

it('validates sentence completion sentence blanks', function (): void {
    $service = app(ListeningQuestionTypeRegistry::class)->serviceFor(ListeningQuestionType::SentenceCompletion);
    $group = new \App\Models\Listening\ListeningQuestionGroup(['start_question_number' => 18, 'end_question_number' => 19]);

    $errors = $service->validatePayload([
        'settings' => [
            'word_limit' => 3,
            'sentences' => [
                ['number' => 18, 'text' => 'Starts at [blank:18].'],
            ],
        ],
    ], $group);

    expect($errors)->toBeEmpty();
});

it('validates summary completion paragraph blanks', function (): void {
    $service = app(ListeningQuestionTypeRegistry::class)->serviceFor(ListeningQuestionType::SummaryCompletion);
    $group = new \App\Models\Listening\ListeningQuestionGroup(['start_question_number' => 20, 'end_question_number' => 21]);

    expect($service->validatePayload([
        'content' => 'Located near the [blank:20] with a [blank:21].',
        'settings' => ['word_limit' => 2],
    ], $group))->toBeEmpty();
});

it('validates short answer requires word limit and answer', function (): void {
    $service = app(ListeningQuestionTypeRegistry::class)->serviceFor(ListeningQuestionType::ShortAnswer);
    $question = new \App\Models\Listening\ListeningQuestion();

    $errors = $service->validatePayload([
        'question_text' => '',
        'word_limit' => 0,
        'correct_answer' => [],
    ], null, $question);

    expect($errors)->not->toBeEmpty();
});
