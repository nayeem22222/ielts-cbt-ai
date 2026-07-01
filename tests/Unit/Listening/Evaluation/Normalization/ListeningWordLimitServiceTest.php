<?php

declare(strict_types=1);

use App\Services\Listening\Evaluation\Normalization\ListeningWordLimitService;

if (! function_exists('normalizationQuestion')) {
    function normalizationQuestion(array $attributes = []): \App\Models\Listening\ListeningQuestion
    {
        return new \App\Models\Listening\ListeningQuestion(array_merge([
            'case_sensitive' => false,
            'allow_punctuation_variation' => true,
            'allow_articles' => true,
            'allow_plural' => true,
            'word_limit' => null,
            'order_sensitive' => false,
        ], $attributes));
    }
}

it('detects word limit exceeded', function (): void {
    $question = normalizationQuestion(['word_limit' => 2]);
    $result = app(ListeningWordLimitService::class)->check([['value' => 'car park ticket', 'type' => 'text']], $question);

    expect($result->exceeded)->toBeTrue()
        ->and($result->wordCount)->toBe(3);
});

it('allows valid word limit answers', function (): void {
    $question = normalizationQuestion(['word_limit' => 2]);
    $result = app(ListeningWordLimitService::class)->check([['value' => 'car park', 'type' => 'text']], $question);

    expect($result->exceeded)->toBeFalse()
        ->and($result->wordCount)->toBe(2);
});

it('counts hyphenated words according to config', function (): void {
    config(['listening.normalization.word_limit.hyphenated_as_one' => false]);

    $service = app(ListeningWordLimitService::class);

    expect($service->countWords('car-park'))->toBe(2);
});
