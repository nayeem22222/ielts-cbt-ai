<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Review;

final readonly class TranscriptHighlightData
{
    /**
     * @param  list<array<string, mixed>>  $lines
     * @param  array<string, mixed>  $highlightedJson
     */
    public function __construct(
        public ?int $transcriptId,
        public ?int $lineStart,
        public ?int $lineEnd,
        public ?string $textSnippet,
        public array $lines,
        public array $highlightedJson,
        public ?string $warning = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'transcript_id' => $this->transcriptId,
            'line_start' => $this->lineStart,
            'line_end' => $this->lineEnd,
            'text_snippet' => $this->textSnippet,
            'lines' => $this->lines,
            'highlighted_transcript' => $this->highlightedJson,
            'warning' => $this->warning,
        ];
    }
}
