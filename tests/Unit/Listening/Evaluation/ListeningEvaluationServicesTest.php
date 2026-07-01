<?php

declare(strict_types=1);

use App\Services\Listening\Evaluation\ListeningAnswerNormalizationService;
use App\Services\Listening\Evaluation\ListeningBandScoreService;

it('normalizes whitespace and case', function (): void {
    $service = app(ListeningAnswerNormalizationService::class);
    $question = new \App\Models\Listening\ListeningQuestion([
        'case_sensitive' => false,
        'allow_punctuation_variation' => false,
        'allow_articles' => false,
    ]);

    $result = $service->normalize([['value' => '  Hello   World  ', 'type' => 'text']], $question, 'text');

    expect($result->primary())->toBe('hello world');
});

it('maps raw scores to IELTS listening bands', function (): void {
    $service = app(ListeningBandScoreService::class);

    expect($service->bandForRawScore(40))->toBe(9.0)
        ->and($service->bandForRawScore(30))->toBe(7.0)
        ->and($service->bandForRawScore(0))->toBe(2.0);
});
