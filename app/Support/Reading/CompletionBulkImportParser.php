<?php

declare(strict_types=1);

namespace App\Support\Reading;

use App\Enums\Exam\OfficialReadingQuestionType;

final class CompletionBulkImportParser
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function parseSentences(string $text): array
    {
        $rows = [];

        foreach (self::lines($text) as $line) {
            $parts = str_contains($line, '|')
                ? array_map('trim', explode('|', $line))
                : array_map('trim', explode("\t", $line));

            if (count($parts) < 3 || ! is_numeric($parts[0])) {
                continue;
            }

            $alternatives = [];

            if (isset($parts[3]) && $parts[3] !== '') {
                $alternatives = array_values(array_filter(array_map(
                    'trim',
                    explode(',', $parts[3]),
                )));
            }

            $rows[] = [
                'question_number' => (int) $parts[0],
                'prompt' => $parts[1],
                'correct_answer' => $parts[2],
                'alternative_answers' => $alternatives,
            ];
        }

        return $rows;
    }

    public static function templateFromImport(string $text, OfficialReadingQuestionType $type): string
    {
        $trimmed = trim($text);

        if ($type === OfficialReadingQuestionType::TableCompletion) {
            return self::markdownTableToHtml($trimmed);
        }

        if ($type === OfficialReadingQuestionType::FlowChartCompletion) {
            return self::flowTextToHtml($trimmed);
        }

        $paragraphs = preg_split("/\R{2,}/", $trimmed) ?: [$trimmed];

        return '<p>'.implode('</p><p>', array_map(
            fn (string $paragraph): string => nl2br(e($paragraph), false),
            array_filter(array_map('trim', $paragraphs)),
        )).'</p>';
    }

    private static function markdownTableToHtml(string $text): string
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $text) ?: [])));

        if ($lines === []) {
            return '';
        }

        $rows = [];

        foreach ($lines as $line) {
            if (! str_contains($line, '|')) {
                continue;
            }

            $cells = array_values(array_filter(array_map('trim', explode('|', $line)), fn (string $cell): bool => $cell !== ''));
            $rows[] = $cells;
        }

        if ($rows === []) {
            return '<p>'.nl2br(e($text), false).'</p>';
        }

        $html = '<table class="completion-table w-full border-collapse"><tbody>';

        foreach ($rows as $rowIndex => $row) {
            $tag = $rowIndex === 0 ? 'th' : 'td';
            $html .= '<tr>';

            foreach ($row as $cell) {
                $html .= '<'.$tag.' class="border border-neutral-300 px-3 py-2">'.self::cellContent($cell).'</'.$tag.'>';
            }

            $html .= '</tr>';
        }

        return $html.'</tbody></table>';
    }

    private static function flowTextToHtml(string $text): string
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $text) ?: [])));
        $html = '<div class="completion-flow space-y-2">';

        foreach ($lines as $line) {
            if (in_array($line, ['↓', '->', '→'], true)) {
                $html .= '<div class="text-center text-lg font-bold text-neutral-500">↓</div>';

                continue;
            }

            $html .= '<div class="rounded-xl border border-neutral-300 bg-white px-4 py-3 text-center">'.self::cellContent($line).'</div>';
        }

        return $html.'</div>';
    }

    private static function cellContent(string $value): string
    {
        if (preg_match('/^\{\{(\d+)\}\}$/', $value, $matches)) {
            return '{{'.$matches[1].'}}';
        }

        if (preg_match('/^\[blank:(\d+)\]$/i', $value, $matches)) {
            return '[Blank:'.$matches[1].']';
        }

        return e($value);
    }

    /**
     * @return list<string>
     */
    private static function lines(string $text): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\R/', $text) ?: []),
            fn (string $line): bool => $line !== '',
        ));
    }
}
