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

it('prefers phrase over stale offsets when type is empty but phrase is set', function (): void {
    expect(ReadingQuestionReferenceSupport::resolveType('', 0, 12, 'some phrase', null))
        ->toBe(ReadingQuestionReferenceSupport::TYPE_PHRASE);
});

it('prefers phrase over explicit offset type when phrase text is set', function (): void {
    expect(ReadingQuestionReferenceSupport::resolveType('offset', 0, 120, 'some phrase', null))
        ->toBe(ReadingQuestionReferenceSupport::TYPE_PHRASE);
});

it('uses offset when only offsets are set without phrase or sentence', function (): void {
    expect(ReadingQuestionReferenceSupport::resolveType('', 0, 12, null, null))
        ->toBe(ReadingQuestionReferenceSupport::TYPE_OFFSET);
});

it('strips smart quotes from saved reference phrase text', function (): void {
    $question = new \App\Models\ReadingQuestion;

    ReadingQuestionReferenceSupport::applyAttributes($question, [
        'reference_phrase' => '“Iron-ore slag, a byproduct of the iron-ore smelting process, can be used in a similar way.”',
        'reference_type' => 'phrase',
    ]);

    expect($question->reference_phrase)
        ->toBe('Iron-ore slag, a byproduct of the iron-ore smelting process, can be used in a similar way.');
});
