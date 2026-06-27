<?php

declare(strict_types=1);

namespace App\Actions\Listening;

use App\DTOs\Listening\TimestampedTranscriptLineData;

class ValidateTimestampedTranscriptAction
{
    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, string>
     */
    public function execute(array $lines, ?float $audioDurationSeconds = null, ?string $plainTranscript = null): array
    {
        $errors = [];
        $seenLines = [];
        $previousEnd = null;

        if ($lines === []) {
            return ['Timestamped transcript must contain at least one line.'];
        }

        $parsed = [];

        foreach ($lines as $index => $line) {
            $row = $index + 1;

            try {
                $parsed[] = TimestampedTranscriptLineData::fromArray($line);
            } catch (\Throwable) {
                $errors[] = "Line {$row}: invalid timestamp entry structure.";

                continue;
            }
        }

        if ($errors !== []) {
            return $errors;
        }

        usort($parsed, static fn (TimestampedTranscriptLineData $a, TimestampedTranscriptLineData $b): int => $a->line <=> $b->line);

        foreach ($parsed as $entry) {
            if (isset($seenLines[$entry->line])) {
                $errors[] = "Duplicate line number {$entry->line} is not allowed.";
            }

            $seenLines[$entry->line] = true;

            if ($entry->end !== null && $entry->start > $entry->end) {
                $errors[] = "Line {$entry->line}: start cannot be greater than end.";
            }

            if ($previousEnd !== null && $entry->start < $previousEnd) {
                $errors[] = "Line {$entry->line}: overlapping timestamps are not allowed (starts before previous line ends).";
            }

            if ($audioDurationSeconds !== null && $entry->end !== null && $entry->end > $audioDurationSeconds) {
                $errors[] = "Line {$entry->line}: end timestamp exceeds audio duration ({$audioDurationSeconds}s).";
            }

            if ($audioDurationSeconds !== null && $entry->end === null && $entry->start > $audioDurationSeconds) {
                $errors[] = "Line {$entry->line}: start timestamp exceeds audio duration ({$audioDurationSeconds}s).";
            }

            if ($plainTranscript !== null && $plainTranscript !== '' && ! $this->textExistsInTranscript($entry->text, $plainTranscript)) {
                $errors[] = "Line {$entry->line}: text does not appear in plain transcript.";
            }

            $previousEnd = $entry->end ?? $entry->start;
        }

        return $errors;
    }

    private function textExistsInTranscript(string $needle, string $haystack): bool
    {
        $needle = mb_strtolower(trim(preg_replace('/\s+/', ' ', $needle) ?? $needle));
        $haystack = mb_strtolower(trim(preg_replace('/\s+/', ' ', $haystack) ?? $haystack));

        if ($needle === '') {
            return false;
        }

        if (str_contains($haystack, $needle)) {
            return true;
        }

        if (mb_strlen($needle) > 20) {
            $snippet = mb_substr($needle, 0, 20);

            return str_contains($haystack, $snippet);
        }

        return false;
    }
}
