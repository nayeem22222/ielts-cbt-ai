<?php

declare(strict_types=1);

namespace App\Services\Listening\QuestionTypes;

use App\Enums\Listening\ListeningQuestionType;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use Illuminate\Database\Eloquent\Collection;

abstract class BaseListeningQuestionTypeService
{
    public function __construct(
        protected readonly CompletionBlankParser $blankParser,
    ) {}

    abstract public function type(): ListeningQuestionType;

    abstract public function label(): string;

    /**
     * @return array<string, mixed>
     */
    abstract public function schema(): array;

    /**
     * @return array<string, mixed>|null
     */
    abstract public function defaultOptions(): ?array;

    /**
     * @return array<string, mixed>
     */
    abstract public function defaultSettings(): array;

    /**
     * @return array<string, mixed>
     */
    abstract public function validationRules(): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    abstract public function normalizePayload(array $payload, ?ListeningQuestionGroup $group = null, ?ListeningQuestion $question = null): array;

    /**
     * @param  array<string, mixed>  $payload
     * @param  Collection<int, ListeningQuestion>|null  $questions
     * @return list<string>
     */
    abstract public function validatePayload(
        array $payload,
        ?ListeningQuestionGroup $group = null,
        ?ListeningQuestion $question = null,
        ?Collection $questions = null,
    ): array;

    /**
     * @param  Collection<int, ListeningQuestion>  $questions
     * @return array<string, mixed>
     */
    abstract public function buildPreviewData(ListeningQuestionGroup $group, Collection $questions): array;

    public function formPartial(): string
    {
        return 'admin.listening.question-types.'.str_replace('_', '-', $this->type()->value).'.form';
    }

    public function previewPartial(): string
    {
        return 'admin.listening.question-types.'.str_replace('_', '-', $this->type()->value).'.preview';
    }

    /**
     * @param  mixed  $options
     * @return array<string, mixed>|list<array<string, mixed>>|null
     */
    protected function normalizeOptions(mixed $options): array|null
    {
        if ($options === null || $options === '' || $options === []) {
            return null;
        }

        if (is_string($options)) {
            $decoded = json_decode($options, true);

            return is_array($decoded) ? $decoded : null;
        }

        return is_array($options) ? $options : null;
    }

    /**
     * @param  mixed  $answers
     * @return list<array<string, mixed>>
     */
    protected function normalizeAnswers(mixed $answers, string $defaultType = 'text'): array
    {
        if ($answers === null || $answers === '' || $answers === []) {
            return [];
        }

        if (is_string($answers)) {
            $decoded = json_decode($answers, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $answers = $decoded;
            } else {
                return [['value' => trim($answers), 'type' => $defaultType]];
            }
        }

        if (! is_array($answers)) {
            return [];
        }

        if (array_is_list($answers)) {
            return array_values(array_map(function (mixed $item) use ($defaultType): array {
                if (! is_array($item)) {
                    return ['value' => (string) $item, 'type' => $defaultType];
                }

                if (isset($item['item_key'])) {
                    return [
                        'item_key' => (string) $item['item_key'],
                        'value' => (string) ($item['value'] ?? ''),
                        'type' => (string) ($item['type'] ?? 'matching'),
                    ];
                }

                if (isset($item['label'], $item['value'])) {
                    return [
                        'label' => (string) $item['label'],
                        'value' => (string) $item['value'],
                        'type' => (string) ($item['type'] ?? 'map_label'),
                    ];
                }

                return [
                    'value' => (string) ($item['value'] ?? ''),
                    'type' => (string) ($item['type'] ?? $defaultType),
                ];
            }, $answers));
        }

        return [];
    }

    /**
     * @return list<string>
     */
    protected function validateQuestionCount(ListeningQuestionGroup $group, Collection $questions): array
    {
        $expected = (int) $group->total_questions;
        $active = $questions->where('is_active', true)->count();

        if ($active < $expected) {
            return ["Group requires {$expected} active questions (currently {$active})."];
        }

        return [];
    }

    /**
     * @param  list<array<string, mixed>>  $correctAnswer
     * @return list<string>
     */
    protected function validateCorrectAnswerPresence(array $correctAnswer, bool $required = true): array
    {
        if (config('listening.questions.allow_draft_without_answer', true)) {
            return [];
        }

        if (! $required) {
            return [];
        }

        foreach ($correctAnswer as $item) {
            $value = trim((string) ($item['value'] ?? ''));

            if ($value !== '') {
                return [];
            }
        }

        return ['Correct answer is required.'];
    }

    /**
     * @param  list<array<string, mixed>>  $options
     * @return list<string>
     */
    protected function validateOptionKeys(array $options, int $minimum = 2): array
    {
        $errors = [];

        if (count($options) < $minimum) {
            $errors[] = "At least {$minimum} options are required.";
        }

        $keys = [];

        foreach ($options as $index => $option) {
            $key = trim((string) ($option['key'] ?? ''));

            if ($key === '') {
                $errors[] = 'Option key is required at position '.($index + 1).'.';
            }

            if (trim((string) ($option['text'] ?? '')) === '') {
                $errors[] = "Option text is required for key {$key}.";
            }

            if ($key !== '' && in_array($key, $keys, true)) {
                $errors[] = "Duplicate option key {$key}.";
            }

            $keys[] = $key;
        }

        return $errors;
    }

  /**
     * @return list<string>
     */
    protected function validateImageRequirement(?string $imagePath, ?array $options): array
    {
        if (! config('listening.question_types.labelling.require_image', true)) {
            return [];
        }

        $path = $imagePath ?? ($options['image']['path'] ?? null);

        if ($path === null || trim((string) $path) === '') {
            return ['Image is required for this question type.'];
        }

        return [];
    }

    /**
     * @param  list<int>  $blankNumbers
     * @return list<string>
     */
    protected function validateTemplateBlanks(array $blankNumbers, int $rangeStart, int $rangeEnd): array
    {
        $errors = [];
        $seen = [];

        foreach ($blankNumbers as $number) {
            if (isset($seen[$number])) {
                $errors[] = "Duplicate blank number {$number}.";
            }

            $seen[$number] = true;

            if ($number < $rangeStart || $number > $rangeEnd) {
                $errors[] = "Blank {$number} is outside group range Q{$rangeStart}–Q{$rangeEnd}.";
            }
        }

        if ($blankNumbers === []) {
            $errors[] = 'At least one blank is required in the template.';
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    protected function answerValues(array $correctAnswer): array
    {
        return array_values(array_filter(array_map(
            fn (array $item): string => trim((string) ($item['value'] ?? '')),
            $correctAnswer,
        )));
    }

    /**
     * @return list<string>
     */
    protected function optionKeysFromList(array $options): array
    {
        return array_values(array_filter(array_map(
            fn (array $option): string => trim((string) ($option['key'] ?? '')),
            $options,
        )));
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    protected function settingsWordLimit(array $settings, ?int $fallback = null): ?int
    {
        return isset($settings['word_limit']) ? (int) $settings['word_limit'] : $fallback;
    }
}
