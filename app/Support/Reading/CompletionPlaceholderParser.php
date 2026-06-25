<?php

declare(strict_types=1);

namespace App\Support\Reading;

use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;
use App\Services\Admin\Exam\ReadingCompletionTemplateService;
use Illuminate\Support\HtmlString;
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
        return self::replacePlaceholders($content, fn (int $number, ?string $label): string => self::previewBadge((string) $number, $label));
    }

    /**
     * @param  array<int, ReadingQuestion>  $questionsByNumber
     */
    public static function renderInteractiveHtml(
        string $content,
        int $testId,
        int $passageId,
        ReadingQuestionGroup $group,
        array $questionsByNumber,
        string $interactionMode = 'input',
    ): HtmlString {
        $type = $group->question_type?->value ?? '';

        if ($interactionMode === 'drag_drop') {
            return new HtmlString(self::renderDragDropPlaceholders(
                $content,
                $testId,
                $passageId,
                $group,
                $questionsByNumber,
                $type,
            ));
        }

        if ($interactionMode === 'select') {
            return new HtmlString(self::renderSelectPlaceholders(
                $content,
                $testId,
                $passageId,
                $group,
                $questionsByNumber,
                $type,
            ));
        }

        $html = self::replacePlaceholders(
            $content,
            function (int $number, ?string $label) use ($testId, $passageId, $group, $questionsByNumber, $type): string {
                $question = $questionsByNumber[$number] ?? null;
                $questionId = $question?->id ?? 0;

                $attrs = sprintf(
                    'data-test-id="%d" data-passage-id="%d" data-group-id="%d" data-question-id="%d" data-question-number="%d" data-question-type="%s"',
                    $testId,
                    $passageId,
                    $group->id,
                    $questionId,
                    $number,
                    e($type),
                );

                $aria = $label ? ' aria-label="'.e($label).'"' : '';

                return sprintf(
                    '<input type="text" class="reading-test-input reading-test-blank" %s%s autocomplete="off" spellcheck="false" />',
                    $attrs,
                    $aria,
                );
            },
        );

        return new HtmlString($html);
    }

    /**
     * @param  array<int, ReadingQuestion>  $questionsByNumber
     */
    public static function renderDragDropPlaceholders(
        string $content,
        int $testId,
        int $passageId,
        ReadingQuestionGroup $group,
        array $questionsByNumber,
        string $type,
    ): string {
        return self::replacePlaceholders(
            $content,
            function (int $number, ?string $label) use ($testId, $passageId, $group, $questionsByNumber, $type): string {
                $question = $questionsByNumber[$number] ?? null;
                $questionId = $question?->id ?? 0;

                $attrs = sprintf(
                    'data-test-id="%d" data-passage-id="%d" data-group-id="%d" data-question-id="%d" data-question-number="%d" data-question-type="%s"',
                    $testId,
                    $passageId,
                    $group->id,
                    $questionId,
                    $number,
                    e($type),
                );

                $aria = $label ? ' aria-label="'.e($label).'"' : '';

                return sprintf(
                    '<span class="reading-dnd-dropzone reading-dnd-dropzone--empty reading-dnd-dropzone--inline reading-test-blank" %s tabindex="0" role="button"%s>'
                    .'<input type="hidden" class="reading-test-input reading-dnd-input" %s value="" />'
                    .'<span class="reading-dnd-dropzone__number">%d</span>'
                    .'<span class="reading-dnd-dropzone__placeholder">Drop answer here</span>'
                    .'<span class="reading-dnd-dropzone__filled" hidden>'
                    .'<span class="reading-dnd-dropzone__key"></span>'
                    .'<span class="reading-dnd-dropzone__label"></span>'
                    .'<button type="button" class="reading-dnd-dropzone__remove" aria-label="Remove answer">&times;</button>'
                    .'</span></span>',
                    $attrs,
                    $aria,
                    $attrs,
                    $number,
                );
            },
        );
    }

    /**
     * @param  array<int, ReadingQuestion>  $questionsByNumber
     */
    public static function renderSelectPlaceholders(
        string $content,
        int $testId,
        int $passageId,
        ReadingQuestionGroup $group,
        array $questionsByNumber,
        string $type,
    ): string {
        $options = $group->groupOptions;

        return self::replacePlaceholders(
            $content,
            function (int $number, ?string $label) use ($testId, $passageId, $group, $questionsByNumber, $type, $options): string {
                $question = $questionsByNumber[$number] ?? null;
                $questionId = $question?->id ?? 0;

                $attrs = sprintf(
                    'data-test-id="%d" data-passage-id="%d" data-group-id="%d" data-question-id="%d" data-question-number="%d" data-question-type="%s"',
                    $testId,
                    $passageId,
                    $group->id,
                    $questionId,
                    $number,
                    e($type),
                );

                $aria = $label ? ' aria-label="'.e($label).'"' : '';
                $optionsHtml = '<option value="">—</option>';
                foreach ($options as $option) {
                    $optionsHtml .= sprintf(
                        '<option value="%s">%s</option>',
                        e($option->option_key),
                        e($option->option_key),
                    );
                }

                return sprintf(
                    '<select class="reading-test-input reading-test-select reading-test-blank" %s%s>%s</select>',
                    $attrs,
                    $aria,
                    $optionsHtml,
                );
            },
        );
    }

    /**
     * @param  callable(int, ?string): string  $replacer
     */
    private static function replacePlaceholders(string $content, callable $replacer): string
    {
        $html = preg_replace_callback(
            '/\{\{\s*(\d+)\s*(?::\s*([a-zA-Z0-9_-]+))?\s*\}\}/',
            fn (array $matches): string => $replacer((int) $matches[1], $matches[2] ?? null),
            $content,
        ) ?? $content;

        return preg_replace_callback(
            '/\[blank:\s*(\d+)\s*(?::\s*([a-zA-Z0-9_-]+))?\s*\]/i',
            fn (array $matches): string => $replacer((int) $matches[1], $matches[2] ?? null),
            $html,
        ) ?? $html;
    }

    private static function previewBadge(string $number, ?string $label): string
    {
        $text = $label ? "{$number}:{$label}" : $number;

        return '<span class="completion-blank inline-flex min-w-[4rem] items-center justify-center rounded border-2 border-dashed border-brand-400 bg-brand-50 px-2 py-0.5 text-xs font-bold text-brand-700">'.$text.'</span>';
    }
}
