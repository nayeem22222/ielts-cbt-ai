<?php

declare(strict_types=1);

use App\Models\Listening\ListeningTranscript;
use App\Services\Listening\Review\ListeningTranscriptHighlightService;

it('finds line by text snippet', function (): void {
    $transcript = new ListeningTranscript([
        'timestamped_transcript' => [
            ['line' => 3, 'text' => 'The answer is museum'],
        ],
    ]);

    $line = app(ListeningTranscriptHighlightService::class)->findLineByTextSnippet($transcript, 'museum');

    expect($line)->not->toBeNull()
        ->and($line['line'])->toBe(3);
});

it('sanitizes transcript for student view', function (): void {
    $payload = ['lines' => [['text' => 'ok']], 'admin_notes' => 'secret'];
    $sanitized = app(ListeningTranscriptHighlightService::class)->sanitizeTranscriptForStudent($payload);

    expect($sanitized)->not->toHaveKey('admin_notes');
});
