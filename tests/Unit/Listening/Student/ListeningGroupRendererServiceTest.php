<?php

declare(strict_types=1);

use App\Services\Listening\Student\ListeningGroupRendererService;

it('renders completion groups with inline numbered blanks instead of question cards', function (): void {
    $renderer = app(ListeningGroupRendererService::class);

    $html = $renderer->render([
        'question_type' => 'form_completion',
        'content' => "Name: [blank:1]\nAddress: [blank:2]",
        'options' => null,
        'settings' => null,
        'image_url' => null,
    ], [
        ['id' => 10, 'question_number' => 1, 'student_answer' => null],
        ['id' => 11, 'question_number' => 2, 'student_answer' => [['value' => 'Oxford', 'type' => 'text']]],
    ]);

    expect($html)
        ->toContain('listening-completion-card')
        ->toContain('listening-completion-template')
        ->toContain('listening-blank')
        ->toContain('listening-blank-number" aria-hidden="true">1</')
        ->toContain('data-question-id="10"')
        ->toContain('value="Oxford"')
        ->not->toContain('Question 1');
});

it('renders mcq groups with grouped stems and options', function (): void {
    $renderer = app(ListeningGroupRendererService::class);

    $html = $renderer->render([
        'question_type' => 'mcq',
        'content' => '',
        'options' => [
            ['key' => 'A', 'text' => 'Hotel'],
            ['key' => 'B', 'text' => 'Museum'],
        ],
        'settings' => null,
        'image_url' => null,
    ], [
        ['id' => 20, 'question_number' => 11, 'question_text' => 'Where did they meet?', 'student_answer' => null],
    ]);

    expect($html)
        ->toContain('listening-question-card')
        ->toContain('listening-mcq-group')
        ->toContain('listening-mcq-radio')
        ->toContain('listening-mcq-option-key')
        ->toContain('11.')
        ->toContain('Where did they meet?')
        ->toContain('A.')
        ->toContain('Hotel')
        ->toContain('type="radio"')
        ->not->toContain('listening-option-letter')
        ->not->toContain('<h3');
});

it('renders multiple answer groups with visible checkboxes and required answer limit metadata', function (): void {
    $renderer = app(ListeningGroupRendererService::class);

    $html = $renderer->render([
        'question_type' => 'multiple_answer',
        'start_question_number' => 21,
        'end_question_number' => 22,
        'content' => '',
        'options' => [
            ['key' => 'A', 'text' => 'Hotel'],
            ['key' => 'B', 'text' => 'Museum'],
        ],
        'settings' => ['required_answers' => 2],
        'image_url' => null,
    ], [
        ['id' => 21, 'question_number' => 21, 'question_text' => 'Choose two places.', 'student_answer' => null],
    ]);

    expect($html)
        ->toContain('listening-multiple-answer-group')
        ->toContain('listening-multiple-answer-checkbox')
        ->toContain('listening-multiple-answer-option-key')
        ->toContain('data-required-answers="2"')
        ->toContain('listening-question-prefix">21–22.</span>')
        ->toContain('A.')
        ->toContain('Hotel')
        ->toContain('type="checkbox"')
        ->not->toContain('listening-option-letter')
        ->not->toContain('listening-mcq-radio');
});

it('renders matching groups with option columns and radio buttons', function (): void {
    $renderer = app(ListeningGroupRendererService::class);

    $html = $renderer->render([
        'question_type' => 'matching',
        'content' => '',
        'options' => [
            'choices' => [
                ['key' => 'A', 'text' => 'being well-organised'],
                ['key' => 'B', 'text' => 'being flexible'],
                ['key' => 'C', 'text' => 'working quickly'],
            ],
            'items' => [
                ['key' => '17', 'text' => 'Prepping an actor'],
                ['key' => '18', 'text' => 'Continuity'],
            ],
        ],
        'settings' => null,
        'image_url' => null,
    ], [
        ['id' => 30, 'question_number' => 17, 'question_text' => 'Prepping an actor', 'student_answer' => [['item_key' => '17', 'value' => 'B', 'type' => 'matching']]],
        ['id' => 31, 'question_number' => 18, 'question_text' => 'Continuity', 'student_answer' => null],
    ]);

    expect($html)
        ->toContain('listening-matching-group')
        ->toContain('listening-matching-options-box')
        ->toContain('being well-organised')
        ->toContain('<table class="listening-matching-table">', false)
        ->toContain('listening-matching-col-option')
        ->toContain('listening-matching-radio')
        ->toContain('type="radio"', false)
        ->toContain('Prepping an actor')
        ->toContain('Continuity')
        ->toContain('name="listening_matching_q_30"', false)
        ->toContain('data-item-key="17"', false)
        ->toContain('value="B"', false)
        ->toContain('checked', false)
        ->not->toContain('<select')
        ->not->toContain('listening-matching-pill-select');
});

it('renders matching drag and drop groups with draggable tokens and drop zones', function (): void {
    $renderer = app(ListeningGroupRendererService::class);

    $html = $renderer->render([
        'id' => 12,
        'question_type' => 'matching',
        'content' => '',
        'settings' => ['interaction_mode' => 'drag_drop'],
        'options' => [
            'choices' => [
                ['key' => 'A', 'text' => 'being well-organised'],
                ['key' => 'B', 'text' => 'being flexible'],
            ],
            'items' => [
                ['key' => '17', 'text' => 'Prepping an actor'],
            ],
        ],
        'image_url' => null,
    ], [
        ['id' => 40, 'question_number' => 17, 'question_text' => 'Prepping an actor', 'student_answer' => [['item_key' => '17', 'value' => 'B', 'type' => 'matching']]],
    ]);

    expect($html)
        ->toContain('listening-dnd-group')
        ->toContain('listening-dnd-token')
        ->toContain('listening-dnd-dropzone')
        ->toContain('data-option-key="A"', false)
        ->toContain('data-group-id="12"', false)
        ->toContain('draggable="true"', false)
        ->toContain('value="B"', false)
        ->not->toContain('type="radio"', false)
        ->not->toContain('<select');
});

it('renders completion drag and drop blanks when interaction mode is drag_drop', function (): void {
    $renderer = app(ListeningGroupRendererService::class);

    $html = $renderer->render([
        'id' => 15,
        'question_type' => 'summary_completion',
        'content' => 'The answer is [blank:7] and [blank:8].',
        'settings' => ['interaction_mode' => 'drag_drop'],
        'options' => [
            ['key' => 'A', 'text' => 'wood'],
            ['key' => 'B', 'text' => 'steel'],
        ],
        'image_url' => null,
    ], [
        ['id' => 50, 'question_number' => 7, 'student_answer' => [['value' => 'A', 'type' => 'letter']]],
        ['id' => 51, 'question_number' => 8, 'student_answer' => null],
    ]);

    expect($html)
        ->toContain('listening-dnd-group')
        ->toContain('listening-dnd-token')
        ->toContain('listening-dnd-dropzone--inline')
        ->toContain('data-question-number="7"', false)
        ->toContain('value="A"', false)
        ->not->toContain('listening-blank-input');
});
