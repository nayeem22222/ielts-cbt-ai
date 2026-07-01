<?php

declare(strict_types=1);

namespace App\Actions\Listening\Review;

use App\DTOs\Listening\Review\TranscriptHighlightData;
use App\Models\Listening\ListeningTranscript;
use App\Services\Listening\Review\ListeningTranscriptHighlightService;

class BuildTranscriptHighlightAction
{
    public function __construct(
        private readonly ListeningTranscriptHighlightService $highlights,
    ) {}

    public function execute(
        ?ListeningTranscript $transcript,
        ?int $lineStart,
        ?int $lineEnd,
        ?string $textSnippet = null,
    ): TranscriptHighlightData {
        return $this->highlights->buildHighlight($transcript, $lineStart, $lineEnd, $textSnippet);
    }
}
