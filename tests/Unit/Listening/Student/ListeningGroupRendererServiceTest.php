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
        ->toContain('11.')
        ->toContain('Where did they meet?')
        ->toContain('type="radio"')
        ->not->toContain('<h3');
});
