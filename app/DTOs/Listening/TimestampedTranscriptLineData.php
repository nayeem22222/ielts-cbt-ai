<?php

declare(strict_types=1);

namespace App\DTOs\Listening;

final readonly class TimestampedTranscriptLineData
{
    public function __construct(
        public int $line,
        public ?string $speaker,
        public float $start,
        public ?float $end,
        public string $text,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            line: (int) ($data['line'] ?? 0),
            speaker: isset($data['speaker']) ? (string) $data['speaker'] : null,
            start: (float) ($data['start'] ?? 0),
            end: array_key_exists('end', $data) && $data['end'] !== null ? (float) $data['end'] : null,
            text: (string) ($data['text'] ?? ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'line' => $this->line,
            'speaker' => $this->speaker,
            'start' => $this->start,
            'end' => $this->end,
            'text' => $this->text,
        ];
    }
}
