<?php

declare(strict_types=1);

namespace App\Services\Listening\Review;

use App\DTOs\Listening\Review\TranscriptHighlightData;
use App\Models\Listening\ListeningTranscript;

class ListeningTranscriptHighlightService
{
    public function buildHighlight(
        ?ListeningTranscript $transcript,
        ?int $lineStart,
        ?int $lineEnd,
        ?string $textSnippet = null,
    ): TranscriptHighlightData {
        if ($transcript === null) {
            return new TranscriptHighlightData(null, $lineStart, $lineEnd, $textSnippet, [], [], 'Transcript not available.');
        }

        $lines = $this->normalizeLines($transcript);

        if ($lines === []) {
            return new TranscriptHighlightData(
                $transcript->id,
                $lineStart,
                $lineEnd,
                $textSnippet,
                [],
                [],
                'Transcript has no timestamped lines.',
            );
        }

        if ($lineStart !== null && $lineEnd !== null) {
            if ($lineStart > $lineEnd || $lineStart < 1) {
                return new TranscriptHighlightData(
                    $transcript->id,
                    $lineStart,
                    $lineEnd,
                    $textSnippet,
                    [],
                    [],
                    'Invalid transcript line range.',
                );
            }

            $matched = $this->findLinesByRange($transcript, $lineStart, $lineEnd);

            if ($matched === []) {
                return new TranscriptHighlightData(
                    $transcript->id,
                    $lineStart,
                    $lineEnd,
                    $textSnippet,
                    [],
                    [],
                    'No transcript lines found for the specified range.',
                );
            }

            return new TranscriptHighlightData(
                $transcript->id,
                $lineStart,
                $lineEnd,
                $textSnippet,
                $matched,
                $this->buildHighlightedTranscriptJson($matched, $lineStart, $lineEnd),
            );
        }

        if ($textSnippet !== null && trim($textSnippet) !== '') {
            $line = $this->findLineByTextSnippet($transcript, $textSnippet);

            if ($line === null) {
                return new TranscriptHighlightData(
                    $transcript->id,
                    null,
                    null,
                    $textSnippet,
                    [],
                    [],
                    'Could not locate transcript snippet.',
                );
            }

            $lineNumber = (int) ($line['line'] ?? 0);
            $matched = $lineNumber > 0 ? [$line] : [];

            return new TranscriptHighlightData(
                $transcript->id,
                $lineNumber ?: null,
                $lineNumber ?: null,
                $textSnippet,
                $matched,
                $this->buildHighlightedTranscriptJson($matched, $lineNumber, $lineNumber),
            );
        }

        return new TranscriptHighlightData(
            $transcript->id,
            $lineStart,
            $lineEnd,
            $textSnippet,
            [],
            [],
            'Insufficient transcript reference data.',
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findLinesByRange(ListeningTranscript $transcript, int $start, int $end): array
    {
        return array_values(array_filter(
            $this->normalizeLines($transcript),
            fn (array $line): bool => isset($line['line'])
                && (int) $line['line'] >= $start
                && (int) $line['line'] <= $end,
        ));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findLineByTextSnippet(ListeningTranscript $transcript, string $snippet): ?array
    {
        $needle = mb_strtolower(trim($snippet));

        if ($needle === '') {
            return null;
        }

        foreach ($this->normalizeLines($transcript) as $line) {
            $text = mb_strtolower((string) ($line['text'] ?? ''));

            if ($text !== '' && str_contains($text, $needle)) {
                return $line;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return array<string, mixed>
     */
    public function buildHighlightedTranscriptJson(array $lines, int $start, int $end): array
    {
        return [
            'line_start' => $start,
            'line_end' => $end,
            'lines' => array_map(function (array $line): array {
                return [
                    'line' => $line['line'] ?? null,
                    'speaker' => $line['speaker'] ?? null,
                    'start' => $line['start'] ?? null,
                    'end' => $line['end'] ?? null,
                    'text' => $line['text'] ?? '',
                    'highlighted' => true,
                ];
            }, $lines),
        ];
    }

    /**
     * @param  array<string, mixed>|string  $transcript
     * @return array<string, mixed>|string
     */
    public function sanitizeTranscriptForStudent(array|string $transcript): array|string
    {
        if (is_string($transcript)) {
            return $transcript;
        }

        unset($transcript['admin_notes'], $transcript['raw_source']);

        return $transcript;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeLines(ListeningTranscript $transcript): array
    {
        $timestamped = $transcript->timestamped_transcript;

        if (! is_array($timestamped) || $timestamped === []) {
            return [];
        }

        return array_values(array_filter($timestamped, fn (mixed $line): bool => is_array($line)));
    }
}
