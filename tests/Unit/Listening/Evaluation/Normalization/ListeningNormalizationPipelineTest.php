<?php

declare(strict_types=1);

use App\Models\Listening\ListeningQuestion;
use App\Services\Listening\Evaluation\Normalization\ListeningNormalizationPipeline;
use App\Services\Listening\Evaluation\Normalization\ListeningRegexAnswerMatcher;

if (! function_exists('normalizationQuestion')) {
    function normalizationQuestion(array $attributes = []): ListeningQuestion
    {
        return new ListeningQuestion(array_merge([
            'case_sensitive' => false,
            'allow_punctuation_variation' => true,
            'allow_articles' => true,
            'allow_plural' => true,
            'word_limit' => null,
            'order_sensitive' => false,
        ], $attributes));
    }
}

it('normalizes lowercase', function (): void {
    $result = app(ListeningNormalizationPipeline::class)
        ->normalize([['value' => 'Library', 'type' => 'text']], normalizationQuestion());

    expect($result->primary())->toBe('library');
});

it('normalizes whitespace', function (): void {
    $result = app(ListeningNormalizationPipeline::class)
        ->normalize([['value' => '  car   park  ', 'type' => 'text']], normalizationQuestion());

    expect($result->primary())->toBe('car park');
});

it('normalizes punctuation', function (): void {
    $result = app(ListeningNormalizationPipeline::class)
        ->normalize([['value' => 'car park.', 'type' => 'text']], normalizationQuestion());

    expect($result->primary())->toBe('car park');
});

it('removes articles', function (): void {
    $result = app(ListeningNormalizationPipeline::class)
        ->normalize([['value' => 'The Library', 'type' => 'text']], normalizationQuestion());

    expect($result->primary())->toBe('library');
});

it('normalizes hyphens', function (): void {
    $result = app(ListeningNormalizationPipeline::class)
        ->normalize([['value' => 'car-park', 'type' => 'text']], normalizationQuestion());

    expect($result->primary())->toBe('car park');
});

it('keeps plural variants available when allowed', function (): void {
    $question = normalizationQuestion(['allow_plural' => true]);
    $plural = app(\App\Services\Listening\Evaluation\Normalization\ListeningPluralNormalizer::class);

    expect($plural->variants('book', $question))->toContain('books');
});

it('does not create plural variants when disabled', function (): void {
    $question = normalizationQuestion(['allow_plural' => false]);
    $plural = app(\App\Services\Listening\Evaluation\Normalization\ListeningPluralNormalizer::class);

    expect($plural->variants('book', $question))->toBe(['book']);
});

it('normalizes British and American spelling when enabled', function (): void {
    config(['listening.normalization.british_american_spelling.enabled' => true]);

    $result = app(ListeningNormalizationPipeline::class)
        ->normalize([['value' => 'colour', 'type' => 'text']], normalizationQuestion());

    expect($result->primary())->toBe('color');
});

it('normalizes number words', function (): void {
    $result = app(ListeningNormalizationPipeline::class)
        ->normalize([['value' => 'twenty five', 'type' => 'number']], normalizationQuestion());

    expect($result->primary())->toBe('25');
});

it('normalizes ordinal dates', function (): void {
    $result = app(ListeningNormalizationPipeline::class)
        ->normalize([['value' => '12th June', 'type' => 'date']], normalizationQuestion());

    expect($result->primary())->toBe('12 june');
});

it('normalizes time formats', function (): void {
    $result = app(ListeningNormalizationPipeline::class)
        ->normalize([['value' => '9 am', 'type' => 'time']], normalizationQuestion());

    expect($result->primary())->toBe('09:00');
});

it('normalizes currency', function (): void {
    $result = app(ListeningNormalizationPipeline::class)
        ->normalize([['value' => '$50', 'type' => 'currency']], normalizationQuestion());

    expect($result->primary())->toBe('50 dollar');
});

it('rejects invalid regex', function (): void {
    expect(app(ListeningRegexAnswerMatcher::class)->validateRegex('/(/'))->toBeFalse();
});

it('lets question override beat config', function (): void {
    config(['listening.normalization.ignore_articles_default' => true]);

    $result = app(ListeningNormalizationPipeline::class)
        ->normalize([['value' => 'the library', 'type' => 'text']], normalizationQuestion(['allow_articles' => false]));

    expect($result->primary())->toBe('the library');
});

it('stores normalization steps', function (): void {
    $result = app(ListeningNormalizationPipeline::class)
        ->normalize([['value' => ' The Library ', 'type' => 'text']], normalizationQuestion());

    expect($result->steps)->not->toBeEmpty()
        ->and($result->steps[0])->toHaveKeys(['step', 'before', 'after']);
});
