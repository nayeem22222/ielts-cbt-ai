<?php

declare(strict_types=1);

use App\Enums\Listening\ListeningQuestionType;
use App\Services\Listening\QuestionTypes\ListeningQuestionTypeRegistry;
use App\Services\Listening\QuestionTypes\McqQuestionTypeService;

it('registry returns all supported types', function (): void {
    $registry = app(ListeningQuestionTypeRegistry::class);
    $types = $registry->all();

    expect($types)->toHaveCount(13);
    expect(collect($types)->map->value->all())->toContain('mcq', 'short_answer', 'matching');
});

it('registry resolves schema and service for mcq', function (): void {
    $registry = app(ListeningQuestionTypeRegistry::class);
    $schema = $registry->get(ListeningQuestionType::MCQ);

    expect($schema->label)->toBe('Multiple Choice');
    expect($registry->serviceFor(ListeningQuestionType::MCQ))->toBeInstanceOf(McqQuestionTypeService::class);
    expect($registry->supportsOptions(ListeningQuestionType::MCQ))->toBeTrue();
});

it('registry rejects disabled type value', function (): void {
    $registry = app(ListeningQuestionTypeRegistry::class);

    expect(fn () => $registry->serviceFor(ListeningQuestionType::MCQ))
        ->not->toThrow(InvalidArgumentException::class);
});
