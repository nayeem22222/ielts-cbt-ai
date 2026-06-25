<?php

declare(strict_types=1);

namespace App\Services\Admin\Exam;

use App\Enums\Exam\ReadingCompletionAnswerRule;
use App\Models\ReadingCorrectAnswer;
use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingTest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReadingCompletionTemplateService
{
    private const PLACEHOLDER_PATTERN = '/\{\{\s*(\d+)\s*(?::\s*([a-zA-Z0-9_-]+))?\s*\}\}|\[blank:\s*(\d+)\s*(?::\s*([a-zA-Z0-9_-]+))?\s*\]/i';

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
    public function parseTemplate(string $template): array
    {
        $template = $this->normalizePlaceholderHtml($template);
        $this->assertNoBrokenPlaceholderSyntax($template);

        if (! preg_match_all(self::PLACEHOLDER_PATTERN, $template, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            return [];
        }

        $placeholders = [];

        foreach ($matches as $match) {
            $rawPlaceholder = $match[0][0];
            $position = (int) $match[0][1];

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
                'raw_placeholder' => $rawPlaceholder,
                'position' => $position,
                'before_text' => '',
                'after_text' => '',
            ];
        }

        return $this->attachSurroundingText($template, $placeholders);
    }

    /**
     * @param  list<array{
     *     question_number: int,
     *     label: ?string,
     *     raw_placeholder: string,
     *     position: int,
     *     before_text: string,
     *     after_text: string
     * }>  $placeholders
     * @return list<array{
     *     question_number: int,
     *     label: ?string,
     *     raw_placeholder: string,
     *     position: int,
     *     before_text: string,
     *     after_text: string
     * }>
     */
    public function validatePlaceholders(ReadingQuestionGroup $group, array $placeholders): array
    {
        if ($group->start_question === null || $group->end_question === null) {
            throw ValidationException::withMessages([
                'question_range' => 'Question group must define a start and end question before syncing a template.',
            ]);
        }

        $seen = [];
        $duplicates = [];
        $outOfRange = [];
        $invalidNumbers = [];

        foreach ($placeholders as $placeholder) {
            $number = (int) $placeholder['question_number'];

            if ($number < 1) {
                $invalidNumbers[] = $number;

                continue;
            }

            if (isset($seen[$number])) {
                $duplicates[] = $number;
            }

            $seen[$number] = true;

            if ($number < (int) $group->start_question || $number > (int) $group->end_question) {
                $outOfRange[] = $number;
            }
        }

        if ($invalidNumbers !== []) {
            throw ValidationException::withMessages([
                'template' => 'Placeholder question numbers must be positive integers.',
            ]);
        }

        if ($duplicates !== []) {
            throw ValidationException::withMessages([
                'template' => 'Duplicate placeholders found for question number(s): '
                    .implode(', ', array_values(array_unique($duplicates))).'.',
            ]);
        }

        if ($outOfRange !== []) {
            throw ValidationException::withMessages([
                'template' => 'Placeholder number(s) outside group range ('.$group->question_range_label.'): '
                    .implode(', ', array_values(array_unique($outOfRange))).'.',
            ]);
        }

        $this->assertNoCrossGroupDuplicates($group, array_keys($seen));

        return $placeholders;
    }

    /**
     * @param  array<int, array<string, mixed>>  $answerData
     * @return array{
     *     created: list<ReadingQuestion>,
     *     updated: list<ReadingQuestion>,
     *     removed_candidates: list<int>,
     *     unchanged: list<int>
     * }
     */
    public function syncQuestions(ReadingQuestionGroup $group, string $template, array $answerData = []): array
    {
        $placeholders = $this->parseTemplate($template);

        if ($placeholders === [] && trim(strip_tags($template)) === '') {
            throw ValidationException::withMessages([
                'template' => 'Template cannot be empty.',
            ]);
        }

        if ($placeholders === [] && trim(strip_tags($template)) !== '') {
            throw ValidationException::withMessages([
                'template' => 'Template must include at least one valid placeholder such as {{27}} or [Blank:27].',
            ]);
        }

        $this->validatePlaceholders($group, $placeholders);

        return DB::transaction(function () use ($group, $placeholders, $answerData): array {
            $created = [];
            $updated = [];
            $unchanged = [];

            $existing = $group->questions()
                ->where('question_number', '>', 0)
                ->get()
                ->keyBy('question_number');

            foreach ($placeholders as $index => $placeholder) {
                $number = (int) $placeholder['question_number'];
                $prompt = $this->buildPrompt($placeholder);
                $sortOrder = $index + 1;
                $metadata = [
                    'auto_generated' => true,
                    'placeholder' => $number,
                    'placeholder_label' => $placeholder['label'],
                    'raw_placeholder' => $placeholder['raw_placeholder'],
                    'position' => $placeholder['position'],
                ];

                /** @var ReadingQuestion|null $question */
                $question = $existing->get($number);

                if ($question === null) {
                    /** @var ReadingQuestion $question */
                    $question = $group->questions()->create([
                        'question_number' => $number,
                        'prompt' => $prompt,
                        'sort_order' => $sortOrder,
                        'marks' => 1,
                        'difficulty' => 'medium',
                        'metadata' => $metadata,
                    ]);

                    $question->correctAnswers()->create([
                        'answer' => '',
                        'answer_json' => null,
                        'matching_key' => null,
                    ]);

                    $created[] = $question;
                } else {
                    $dirty = false;

                    if ($question->prompt !== $prompt) {
                        $question->prompt = $prompt;
                        $dirty = true;
                    }

                    if ((int) $question->sort_order !== $sortOrder) {
                        $question->sort_order = $sortOrder;
                        $dirty = true;
                    }

                    $mergedMetadata = array_merge($question->metadata ?? [], $metadata);

                    if ($mergedMetadata !== ($question->metadata ?? [])) {
                        $question->metadata = $mergedMetadata;
                        $dirty = true;
                    }

                    if ($dirty) {
                        $question->save();
                        $updated[] = $question;
                    } else {
                        $unchanged[] = $number;
                    }
                }

                if (isset($answerData[$number])) {
                    $this->syncCorrectAnswers($question, $answerData[$number]);
                }
            }

            $placeholderNumbers = array_map(
                fn (array $placeholder): int => (int) $placeholder['question_number'],
                $placeholders,
            );

            $removedCandidates = $this->detectRemovedQuestions($group, $placeholderNumbers);

            return [
                'created' => $created,
                'updated' => $updated,
                'removed_candidates' => $removedCandidates,
                'unchanged' => $unchanged,
            ];
        });
    }

    /**
     * @param  array{
     *     answers?: list<string>,
     *     correct_answer?: string,
     *     alternative_answers?: list<string>,
     *     case_sensitive?: bool,
     *     word_limit?: string,
     *     regex?: ?string
     * }  $answers
     */
    public function syncCorrectAnswers(ReadingQuestion $question, array $answers): void
    {
        $caseSensitive = (bool) ($answers['case_sensitive'] ?? false);
        $wordLimit = $this->normalizeWordLimit((string) ($answers['word_limit'] ?? ReadingCompletionAnswerRule::OneWordOnly->value));
        $regex = $answers['regex'] ?? null;

        $rawAnswers = $answers['answers'] ?? null;

        if (! is_array($rawAnswers)) {
            $primary = trim((string) ($answers['correct_answer'] ?? ''));
            $alternatives = array_map(
                fn ($value) => trim((string) $value),
                $answers['alternative_answers'] ?? [],
            );

            $rawAnswers = array_values(array_filter(
                array_merge([$primary], $alternatives),
                fn (string $value): bool => $value !== '',
            ));
        }

        $rawAnswers = array_values(array_unique(array_map(
            fn ($value) => trim((string) $value),
            $rawAnswers,
        )));

        if ($rawAnswers === []) {
            throw ValidationException::withMessages([
                'answers' => 'At least one correct answer is required.',
            ]);
        }

        $normalizedAnswers = array_map(
            fn (string $answer) => $this->normalizeAnswer($answer, $caseSensitive),
            $rawAnswers,
        );

        $primary = $rawAnswers[0];

        $payload = [
            'answers' => $rawAnswers,
            'case_sensitive' => $caseSensitive,
            'word_limit' => $wordLimit,
            'regex' => $regex,
        ];

        ReadingCorrectAnswer::query()->where('question_id', $question->id)->delete();

        ReadingCorrectAnswer::query()->create([
            'question_id' => $question->id,
            'answer' => $primary,
            'answer_json' => $payload,
            'matching_key' => null,
        ]);
    }

    /**
     * @param  list<int>  $placeholderNumbers
     * @return list<int>
     */
    public function detectRemovedQuestions(ReadingQuestionGroup $group, array $placeholderNumbers): array
    {
        $existingNumbers = $group->questions()
            ->where('question_number', '>', 0)
            ->pluck('question_number')
            ->map(fn ($value) => (int) $value)
            ->all();

        return array_values(array_diff($existingNumbers, $placeholderNumbers));
    }

    public function normalizeAnswer(string $answer, bool $caseSensitive = false): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($answer)) ?? trim($answer);

        return $caseSensitive ? $normalized : mb_strtolower($normalized);
    }

    /**
     * @param  list<int>  $questionNumbers
     */
    public function purgeRemovedQuestions(ReadingQuestionGroup $group, array $questionNumbers): int
    {
        if ($questionNumbers === []) {
            return 0;
        }

        return DB::transaction(function () use ($group, $questionNumbers): int {
            return $group->questions()
                ->whereIn('question_number', $questionNumbers)
                ->delete();
        });
    }

    /**
     * @param  list<array<string, mixed>>  $placeholders
     * @return list<array<string, mixed>>
     */
    private function attachSurroundingText(string $template, array $placeholders): array
    {
        $length = strlen($template);
        $count = count($placeholders);

        foreach ($placeholders as $index => &$placeholder) {
            $start = (int) $placeholder['position'];
            $rawLength = strlen((string) $placeholder['raw_placeholder']);
            $end = $start + $rawLength;

            $previousEnd = $index > 0
                ? (int) $placeholders[$index - 1]['position'] + strlen((string) $placeholders[$index - 1]['raw_placeholder'])
                : 0;

            $nextStart = $index < ($count - 1)
                ? (int) $placeholders[$index + 1]['position']
                : $length;

            $placeholder['before_text'] = substr($template, $previousEnd, $start - $previousEnd);
            $placeholder['after_text'] = substr($template, $end, $nextStart - $end);
        }

        unset($placeholder);

        return $placeholders;
    }

    /**
     * @param  array<string, mixed>  $placeholder
     */
    private function buildPrompt(array $placeholder): string
    {
        if (! empty($placeholder['label'])) {
            return (string) $placeholder['label'];
        }

        $before = trim(strip_tags((string) ($placeholder['before_text'] ?? '')));
        $after = trim(strip_tags((string) ($placeholder['after_text'] ?? '')));

        if ($before === '' && $after === '') {
            return '';
        }

        return trim($before.' _____ '.$after);
    }

    private function normalizeWordLimit(string $wordLimit): string
    {
        $upper = strtoupper(str_replace(['-', ' '], '_', trim($wordLimit)));

        if (in_array($upper, [
            'ONE_WORD',
            'ONE_WORD_ONLY',
            'ONE_WORD_AND_OR_A_NUMBER',
            'ONE_WORD_AND_OR_NUMBER',
            'TWO_WORDS',
            'THREE_WORDS',
            'CUSTOM',
        ], true)) {
            return $upper === 'ONE_WORD_AND_OR_NUMBER' ? 'ONE_WORD_AND_OR_A_NUMBER' : $upper;
        }

        $enum = ReadingCompletionAnswerRule::tryFrom(strtolower($wordLimit));

        return match ($enum) {
            ReadingCompletionAnswerRule::OneWord => 'ONE_WORD',
            ReadingCompletionAnswerRule::OneWordOnly => 'ONE_WORD_ONLY',
            ReadingCompletionAnswerRule::OneWordAndOrNumber => 'ONE_WORD_AND_OR_A_NUMBER',
            ReadingCompletionAnswerRule::TwoWords => 'TWO_WORDS',
            ReadingCompletionAnswerRule::ThreeWords => 'THREE_WORDS',
            ReadingCompletionAnswerRule::Custom => 'CUSTOM',
            default => 'ONE_WORD_ONLY',
        };
    }

    /**
     * @param  list<int>  $questionNumbers
     */
    private function assertNoCrossGroupDuplicates(ReadingQuestionGroup $group, array $questionNumbers): void
    {
        if ($questionNumbers === []) {
            return;
        }

        /** @var ReadingTest $test */
        $test = $group->passage()->firstOrFail()->test()->firstOrFail();

        $conflicts = $test->questions()
            ->whereIn('question_number', $questionNumbers)
            ->where('question_number', '>', 0)
            ->whereHas('group', fn ($query) => $query->whereKeyNot($group->id))
            ->pluck('question_number')
            ->all();

        if ($conflicts !== []) {
            throw ValidationException::withMessages([
                'template' => 'Question number(s) already used elsewhere in this reading test: '
                    .implode(', ', array_map('strval', $conflicts)).'.',
            ]);
        }
    }

    private function normalizePlaceholderHtml(string $template): string
    {
        $template = preg_replace(
            '/<span[^>]*>\s*(\{\{\s*\d+\s*(?::\s*[a-zA-Z0-9_-]+)?\s*\}\}|\[blank:\s*\d+\s*(?::\s*[a-zA-Z0-9_-]+)?\s*\])\s*<\/span>/i',
            '$1',
            $template,
        ) ?? $template;

        $template = preg_replace_callback(
            '/\{\{((?:[^}]|<[^>]*>)*)\}\}/',
            static function (array $matches): string {
                $inner = preg_replace('/<[^>]*>/', '', $matches[1]) ?? $matches[1];
                $inner = preg_replace('/\s+/', ' ', trim($inner)) ?? trim($inner);

                return '{{'.$inner.'}}';
            },
            $template,
        ) ?? $template;

        $template = preg_replace_callback(
            '/\[blank:\s*((?:[^\]]|<[^>]*>)*)\]/i',
            static function (array $matches): string {
                $inner = preg_replace('/<[^>]*>/', '', $matches[1]) ?? $matches[1];
                $inner = preg_replace('/\s+/', ' ', trim($inner)) ?? trim($inner);

                return '[blank:'.$inner.']';
            },
            $template,
        ) ?? $template;

        return $template;
    }

    private function assertNoBrokenPlaceholderSyntax(string $template): void
    {
        if (preg_match_all('/\{\{[^}]*\}\}/', $template, $braceMatches)) {
            foreach ($braceMatches[0] as $candidate) {
                if (! preg_match('/^\{\{\s*\d+\s*(?::\s*[a-zA-Z0-9_-]+)?\s*\}\}$/', $candidate)) {
                    throw ValidationException::withMessages([
                        'template' => "Broken placeholder syntax near [{$candidate}].",
                    ]);
                }
            }
        }

        if (preg_match_all('/\[blank:[^\]]*\]/i', $template, $bracketMatches)) {
            foreach ($bracketMatches[0] as $candidate) {
                if (! preg_match('/^\[blank:\s*\d+\s*(?::\s*[a-zA-Z0-9_-]+)?\s*\]$/i', $candidate)) {
                    throw ValidationException::withMessages([
                        'template' => "Broken placeholder syntax near [{$candidate}].",
                    ]);
                }
            }
        }

        if (preg_match('/\{\{(?!\s*\d+)/', $template) || preg_match('/\{\{[^}]*$/', $template)) {
            throw ValidationException::withMessages([
                'template' => 'Broken placeholder syntax: incomplete {{ }} block.',
            ]);
        }

        if (preg_match('/\[blank:(?!\s*\d+)/i', $template) || preg_match('/\[blank:[^\]]*$/i', $template)) {
            throw ValidationException::withMessages([
                'template' => 'Broken placeholder syntax: incomplete [Blank:] block.',
            ]);
        }
    }
}
