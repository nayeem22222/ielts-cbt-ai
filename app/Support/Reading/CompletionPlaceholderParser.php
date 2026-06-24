<?php

declare(strict_types=1);

namespace App\Support\Reading;

use App\Models\ReadingQuestionGroup;
use Illuminate\Validation\ValidationException;

final class CompletionPlaceholderParser
{
  /**
     * @return list<int>
     */
    public static function extractNumbers(string $content): array
    {
        $numbers = [];

        if (preg_match_all('/\{\{(\d+)\}\}/', $content, $braceMatches)) {
            foreach ($braceMatches[1] as $number) {
                $numbers[] = (int) $number;
            }
        }

        if (preg_match_all('/\[blank:(\d+)\]/i', $content, $bracketMatches)) {
            foreach ($bracketMatches[1] as $number) {
                $numbers[] = (int) $number;
            }
        }

        $unique = array_values(array_unique($numbers));
        sort($unique);

        return $unique;
    }

    /**
     * @return array<int, int>
     */
    public static function occurrenceCounts(string $content): array
    {
        $counts = [];

        if (preg_match_all('/\{\{(\d+)\}\}/', $content, $braceMatches)) {
            foreach ($braceMatches[1] as $number) {
                $key = (int) $number;
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
        }

        if (preg_match_all('/\[blank:(\d+)\]/i', $content, $bracketMatches)) {
            foreach ($bracketMatches[1] as $number) {
                $key = (int) $number;
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
        }

        ksort($counts);

        return $counts;
    }

    public static function validateTemplate(string $content, ReadingQuestionGroup $group): void
    {
        $counts = self::occurrenceCounts($content);
        $duplicates = array_keys(array_filter($counts, fn (int $count): bool => $count > 1));

        if ($duplicates !== []) {
            throw ValidationException::withMessages([
                'template_html' => 'Duplicate placeholders found for question number(s): '.implode(', ', $duplicates).'.',
            ]);
        }

        $numbers = array_keys($counts);

        if ($numbers === [] && trim(strip_tags($content)) !== '') {
            throw ValidationException::withMessages([
                'template_html' => 'Template must include at least one blank placeholder such as {{27}} or [Blank:27].',
            ]);
        }

        $outOfRange = [];

        foreach ($numbers as $number) {
            if ($group->start_question !== null && $number < $group->start_question) {
                $outOfRange[] = $number;
            }

            if ($group->end_question !== null && $number > $group->end_question) {
                $outOfRange[] = $number;
            }
        }

        if ($outOfRange !== []) {
            throw ValidationException::withMessages([
                'template_html' => 'Placeholder number(s) outside group range ('.$group->question_range_label.'): '
                    .implode(', ', array_unique($outOfRange)).'.',
            ]);
        }
    }

    public static function renderPreviewHtml(string $content): string
    {
        $html = $content;

        $html = preg_replace_callback(
            '/\{\{(\d+)\}\}/',
            fn (array $matches): string => '<span class="completion-blank inline-flex min-w-[4rem] items-center justify-center rounded border-2 border-dashed border-brand-400 bg-brand-50 px-2 py-0.5 text-xs font-bold text-brand-700">'.$matches[1].'</span>',
            $html,
        ) ?? $html;

        return preg_replace_callback(
            '/\[blank:(\d+)\]/i',
            fn (array $matches): string => '<span class="completion-blank inline-flex min-w-[4rem] items-center justify-center rounded border-2 border-dashed border-brand-400 bg-brand-50 px-2 py-0.5 text-xs font-bold text-brand-700">'.$matches[1].'</span>',
            $html,
        ) ?? $html;
    }
}
