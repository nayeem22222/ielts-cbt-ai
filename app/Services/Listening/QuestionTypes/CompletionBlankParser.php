<?php

declare(strict_types=1);

namespace App\Services\Listening\QuestionTypes;

use App\Support\Listening\ListeningContentRenderer;
use Illuminate\Support\Collection;

class CompletionBlankParser
{
    private const PLACEHOLDER_PATTERN = '/\{\{\s*(\d+)\s*(?::\s*([a-zA-Z0-9_-]+))?\s*\}\}|\[blank:\s*(\d+)\s*(?::\s*([a-zA-Z0-9_-]+))?\s*\]/i';

    /**
     * @return list<int>
     */
    public function extractBlankNumbers(string $content): array
    {
        return array_map(
            fn (array $placeholder): int => (int) $placeholder['question_number'],
            $this->parsePlaceholders($content),
        );
    }

    /**
     * @return list<array{
     *     question_number: int,
     *     label: ?string,
     *     raw_placeholder: string,
     *     position: int
     * }>
     */
    public function parsePlaceholders(string $content): array
    {
        $normalized = $this->normalizePlaceholderHtml($content);

        $normalized = preg_replace('/(?<!\{)\{(\d+)\}(?!\})/', '{{$1}}', $normalized) ?? $normalized;

        if (! preg_match_all(self::PLACEHOLDER_PATTERN, $normalized, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            return [];
        }

        $placeholders = [];

        foreach ($matches as $match) {
            if (($match[1][0] ?? '') !== '') {
                $questionNumber = (int) $match[1][0];
                $label = ($match[2][0] ?? '') !== '' ? (string) $match[2][0] : null;
            } else {
                $questionNumber = (int) $match[3][0];
                $label = ($match[4][0] ?? '') !== '' ? (string) $match[4][0] : null;
            }

            $placeholders[] = [
                'question_number' => $questionNumber,
                'label' => $label,
                'raw_placeholder' => $match[0][0],
                'position' => (int) $match[0][1],
            ];
        }

        return $placeholders;
    }

    /**
     * @param  Collection<int, \App\Models\Listening\ListeningQuestion>  $questions
     * @return list<string>
     */
    public function validateBlanksAgainstQuestions(
        string $content,
        Collection $questions,
        int $rangeStart,
        int $rangeEnd,
    ): array {
        $errors = [];
        $blankNumbers = $this->extractBlankNumbers($content);
        $questionNumbers = $questions->pluck('question_number')->map(fn ($n) => (int) $n)->all();
        $seen = [];

        foreach ($blankNumbers as $number) {
            if (isset($seen[$number])) {
                $errors[] = "Duplicate blank number [blank:{$number}] in content.";

                continue;
            }

            $seen[$number] = true;

            if ($number < $rangeStart || $number > $rangeEnd) {
                $errors[] = "Blank [blank:{$number}] is outside group range Q{$rangeStart}–Q{$rangeEnd}.";
            }

            if (! in_array($number, $questionNumbers, true)) {
                $errors[] = "Blank [blank:{$number}] has no matching question.";
            }
        }

        return $errors;
    }

    public function replaceBlanksForAdminPreview(string $content): string
    {
        $normalized = $this->normalizePlaceholderHtml($content);

        return (string) preg_replace_callback(self::PLACEHOLDER_PATTERN, function (array $matches): string {
            $number = ($matches[1] ?? '') !== '' ? $matches[1] : $matches[3];

            return '<span class="inline-flex items-center rounded border border-amber-300 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200">Blank '.$number.'</span>';
        }, $normalized);
    }

    public function replaceBlanksForFutureStudentPreview(string $content): string
    {
        $normalized = $this->normalizePlaceholderHtml($content);

        return (string) preg_replace_callback(self::PLACEHOLDER_PATTERN, function (array $matches): string {
            $number = ($matches[1] ?? '') !== '' ? $matches[1] : $matches[3];

            return '<input type="text" name="answer_'.$number.'" class="inline-block w-32 border-b border-neutral-400 bg-transparent" placeholder="..." disabled />';
        }, $normalized);
    }

    /**
     * @param  array<int, array<string, mixed>>  $questionsByNumber
     */
    public function renderStudentInteractive(string $content, array $questionsByNumber): string
    {
        $normalized = ListeningContentRenderer::sanitizeEditorHtml(
            $this->normalizePlaceholderHtml($content),
        );

        return (string) preg_replace_callback(self::PLACEHOLDER_PATTERN, function (array $matches) use ($questionsByNumber): string {
            $number = (int) (($matches[1] ?? '') !== '' ? $matches[1] : $matches[3]);
            $question = $questionsByNumber[$number] ?? null;

            return $this->blankInputMarkup(
                $number,
                (int) ($question['id'] ?? 0),
                $this->savedTextValue($question),
            );
        }, $normalized);
    }

    private function blankInputMarkup(int $number, int $questionId, string $value): string
    {
        $valueAttr = $value !== '' ? ' value="'.e($value).'"' : '';

        return sprintf(
            '<span class="listening-blank" data-question-number="%1$d"><span class="listening-blank-pill"><span class="listening-blank-number">%1$d</span><input type="text" class="listening-answer-input listening-blank-input" data-question-id="%2$d" data-question-number="%1$d" maxlength="120"%3$s /></span></span>',
            $number,
            $questionId,
            $valueAttr,
        );
    }

    /**
     * @param  array<string, mixed>|null  $question
     */
    private function savedTextValue(?array $question): string
    {
        if ($question === null) {
            return '';
        }

        $answers = $question['student_answer'] ?? null;

        if (! is_array($answers)) {
            return '';
        }

        foreach ($answers as $answer) {
            if (! is_array($answer)) {
                continue;
            }

            $value = trim((string) ($answer['value'] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    public function normalizePlaceholderHtml(string $content): string
    {
        $content = preg_replace(
            '/<span[^>]*data-completion-blank=["\']?(\d+)["\']?[^>]*>.*?<\/span>/is',
            '[blank:$1]',
            $content,
        ) ?? $content;

        $content = preg_replace(
            '/<span[^>]*>\s*(\{\{\s*\d+\s*(?::\s*[a-zA-Z0-9_-]+)?\s*\}\}|\[blank:\s*\d+\s*(?::\s*[a-zA-Z0-9_-]+)?\s*\])\s*<\/span>/i',
            '$1',
            $content,
        ) ?? $content;

        $content = preg_replace_callback(
            '/\{\{((?:[^}]|<[^>]*>)*)\}\}/',
            static function (array $matches): string {
                $inner = preg_replace('/<[^>]*>/', '', $matches[1]) ?? $matches[1];
                $inner = preg_replace('/\s+/', ' ', trim($inner)) ?? trim($inner);

                return '{{'.$inner.'}}';
            },
            $content,
        ) ?? $content;

        $content = preg_replace_callback(
            '/\[blank:\s*((?:[^\]]|<[^>]*>)*)\]/i',
            static function (array $matches): string {
                $inner = preg_replace('/<[^>]*>/', '', $matches[1]) ?? $matches[1];
                $inner = preg_replace('/\s+/', ' ', trim($inner)) ?? trim($inner);

                return '[blank:'.$inner.']';
            },
            $content,
        ) ?? $content;

        return preg_replace('/(?<!\{)\{(\d+)\}(?!\})/', '{{$1}}', $content) ?? $content;
    }
}
