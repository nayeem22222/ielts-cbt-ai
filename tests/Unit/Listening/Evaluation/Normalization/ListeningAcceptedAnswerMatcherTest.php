<?php

declare(strict_types=1);

use App\Services\Listening\Evaluation\Normalization\ListeningAcceptedAnswerMatcher;
use App\Support\Listening\Evaluation\ListeningMatchReason;

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

it('matches accepted alternatives', function (): void {
    $match = app(ListeningAcceptedAnswerMatcher::class)->match(
        studentAnswer: [['value' => 'the library', 'type' => 'text']],
        correctAnswers: [['value' => 'library', 'type' => 'text']],
        acceptedAnswers: [['value' => 'the library', 'type' => 'text']],
        question: normalizationQuestion(),
    );

    expect($match->matched)->toBeTrue()
        ->and($match->matchReason)->toBe(ListeningMatchReason::EXACT_MATCH);
});

it('matches regex accepted answers', function (): void {
    $match = app(ListeningAcceptedAnswerMatcher::class)->match(
        studentAnswer: [['value' => 'libraries', 'type' => 'text']],
        correctAnswers: [['value' => 'library', 'type' => 'text']],
        acceptedAnswers: [['value' => '/^libra(ries|ry)$/i', 'type' => 'regex']],
        question: normalizationQuestion(),
    );

    expect($match->matched)->toBeTrue();
});

it('matches multiple answers order-insensitively', function (): void {
    $match = app(ListeningAcceptedAnswerMatcher::class)->matchSet(
        studentAnswer: [
            ['value' => 'C', 'type' => 'letter'],
            ['value' => 'A', 'type' => 'letter'],
        ],
        correctAnswers: [
            ['value' => 'A', 'type' => 'letter'],
            ['value' => 'C', 'type' => 'letter'],
        ],
        question: normalizationQuestion(['order_sensitive' => false]),
        orderSensitive: false,
    );

    expect($match->matched)->toBeTrue();
});

it('fails multiple answers when order-sensitive order differs', function (): void {
    $match = app(ListeningAcceptedAnswerMatcher::class)->matchSet(
        studentAnswer: [
            ['value' => 'C', 'type' => 'letter'],
            ['value' => 'A', 'type' => 'letter'],
        ],
        correctAnswers: [
            ['value' => 'A', 'type' => 'letter'],
            ['value' => 'C', 'type' => 'letter'],
        ],
        question: normalizationQuestion(['order_sensitive' => true]),
        orderSensitive: true,
    );

    expect($match->matched)->toBeFalse();
});

it('normalizes matching letters', function (): void {
    $match = app(ListeningAcceptedAnswerMatcher::class)->match(
        studentAnswer: [['item_key' => '17', 'value' => ' b ', 'type' => 'matching']],
        correctAnswers: [['item_key' => '17', 'value' => 'B', 'type' => 'matching']],
        acceptedAnswers: [],
        question: normalizationQuestion(),
    );

    expect($match->matched)->toBeTrue()
        ->and($match->normalizedStudentAnswer->primary())->toBe('B');
});

it('normalizes labelling answers', function (): void {
    $match = app(ListeningAcceptedAnswerMatcher::class)->match(
        studentAnswer: [['label' => ' c ', 'value' => ' c ', 'type' => 'map_label']],
        correctAnswers: [['label' => 'C', 'value' => 'C', 'type' => 'map_label']],
        acceptedAnswers: [],
        question: normalizationQuestion(),
    );

    expect($match->matched)->toBeTrue()
        ->and($match->normalizedStudentAnswer->primary())->toBe('C');
});

it('does not expose correct answers in normalized student data', function (): void {
    $match = app(ListeningAcceptedAnswerMatcher::class)->match(
        studentAnswer: [['value' => 'wrong', 'type' => 'text']],
        correctAnswers: [['value' => 'secret', 'type' => 'text']],
        acceptedAnswers: [],
        question: normalizationQuestion(),
    );

    expect($match->normalizedStudentAnswer->values)->toBe(['wrong'])
        ->and($match->normalizedStudentAnswer->values)->not->toContain('secret');
});
