<?php

declare(strict_types=1);

namespace App\Support\Reading;

use App\Models\ReadingQuestionGroup;
use App\Services\Admin\Exam\ReadingCompletionTemplateService;
use Illuminate\Validation\ValidationException;

final class CompletionPlaceholderParser
{
    public static function service(): ReadingCompletionTemplateService
    {
        return app(ReadingCompletionTemplateService::class);
    }

    /**
     * @return list<array{
     *     question_number: int,
     *     label: ?string,
     *     raw_placeholder: string,
     *     position: int,
     *     before_text: string,
     *     after_text: string
     * }>
     */
    public static function parse(string $content): array
    {
        return self::service()->parseTemplate($content);
    }

    /**
     * @return list<int>
     */
    public static function extractNumbers(string $content): array
    {
        $numbers = array_map(
            fn (array $placeholder): int => (int) $placeholder['question_number'],
            self::parse($content),
        );

        return array_values(array_unique($numbers));
    }

    /**
     * @return array<int, int>
     */
    public static function occurrenceCounts(string $content): array
    {
        $counts = [];

        foreach (self::parse($content) as $placeholder) {
            $key = (int) $placeholder['question_number'];
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        ksort($counts);

        return $counts;
    }

    public static function validateTemplate(string $content, ReadingQuestionGroup $group): void
    {
        if (trim(strip_tags($content)) === '') {
            throw ValidationException::withMessages([
                'template_html' => 'Template cannot be empty.',
            ]);
        }

        try {
            self::service()->validatePlaceholders($group, self::parse($content));
        } catch (ValidationException $exception) {
            $messages = $exception->errors();
            $first = $messages['template'][0] ?? $messages['question_range'][0] ?? 'Invalid template placeholders.';

            throw ValidationException::withMessages([
                'template_html' => $first,
            ]);
        }
    }

    public static function renderPreviewHtml(string $content): string
    {
        $html = $content;

        $html = preg_replace_callback(
            '/\{\{\s*(\d+)\s*(?::\s*([a-zA-Z0-9_-]+))?\s*\}\}/',
            fn (array $matches): string => self::previewBadge($matches[1], $matches[2] ?? null),
            $html,
        ) ?? $html;

        return preg_replace_callback(
            '/\[blank:\s*(\d+)\s*(?::\s*([a-zA-Z0-9_-]+))?\s*\]/i',
            fn (array $matches): string => self::previewBadge($matches[1], $matches[2] ?? null),
            $html,
        ) ?? $html;
    }

    private static function previewBadge(string $number, ?string $label): string
    {
        $text = $label ? "{$number}:{$label}" : $number;

        return '<span class="completion-blank inline-flex min-w-[4rem] items-center justify-center rounded border-2 border-dashed border-brand-400 bg-brand-50 px-2 py-0.5 text-xs font-bold text-brand-700">'.$text.'</span>';
    }
}
