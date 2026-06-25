<?php

declare(strict_types=1);

use App\Support\Reading\ReadingQuestionReferenceSupport;

it('resolves offset type when start and end offsets exist', function (): void {
    expect(ReadingQuestionReferenceSupport::resolveType(null, 0, 12, null, null))
        ->toBe(ReadingQuestionReferenceSupport::TYPE_OFFSET);
});

it('resolves phrase type when phrase is provided without explicit type', function (): void {
    expect(ReadingQuestionReferenceSupport::resolveType(null, null, null, 'urban transport', null))
        ->toBe(ReadingQuestionReferenceSupport::TYPE_PHRASE);
});

it('resolves sentence type when sentence is provided without explicit type', function (): void {
    expect(ReadingQuestionReferenceSupport::resolveType(null, null, null, null, 'City planners now prefer sustainable options.'))
        ->toBe(ReadingQuestionReferenceSupport::TYPE_SENTENCE);
});

it('prefers explicit reference type when provided', function (): void {
    expect(ReadingQuestionReferenceSupport::resolveType('phrase', 0, 12, 'ignored', null))
        ->toBe(ReadingQuestionReferenceSupport::TYPE_PHRASE);
});

it('prioritizes offset auto-detection before phrase when type is empty', function (): void {
    expect(ReadingQuestionReferenceSupport::resolveType('', 0, 12, 'some phrase', null))
        ->toBe(ReadingQuestionReferenceSupport::TYPE_OFFSET);
});
