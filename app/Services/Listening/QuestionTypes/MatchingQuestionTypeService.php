<?php

declare(strict_types=1);

namespace App\Services\Listening\QuestionTypes;

use App\Enums\Listening\ListeningAnswerFormat;
use App\Enums\Listening\ListeningLayoutType;
use App\Enums\Listening\ListeningQuestionType;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use Illuminate\Database\Eloquent\Collection;

class MatchingQuestionTypeService extends BaseListeningQuestionTypeService
{
    public function type(): ListeningQuestionType
    {
        return ListeningQuestionType::Matching;
    }

    public function label(): string
    {
        return 'Matching';
    }

    public function schema(): array
    {
        return [
            'default_layout' => ListeningLayoutType::Default->value,
            'default_answer_format' => ListeningAnswerFormat::Letter->value,
            'supports_options' => true,
            'required_group_fields' => ['options'],
        ];
    }

    public function defaultOptions(): ?array
    {
        return [
            'items' => [],
            'choices' => [
                ['key' => 'A', 'text' => ''],
                ['key' => 'B', 'text' => ''],
            ],
            'allow_choice_reuse' => config('listening.question_types.matching.allow_choice_reuse_default', false),
        ];
    }

    public function defaultSettings(): array
    {
        return [];
    }

    public function validationRules(): array
    {
        return ['options' => ['required', 'array']];
    }

    public function normalizePayload(array $payload, ?ListeningQuestionGroup $group = null, ?ListeningQuestion $question = null): array
    {
        $resolved = $this->resolveOptions($payload, $group);

        if ($resolved !== []) {
            $payload['options'] = $this->normalizeMatchingOptions($resolved, $group);
        }

        if (isset($payload['correct_answer'])) {
            $payload['correct_answer'] = $this->normalizeAnswers($payload['correct_answer'], 'letter');
            $payload['answer_format'] = ListeningAnswerFormat::Letter->value;
        }

        return $payload;
    }

    public function validatePayload(
        array $payload,
        ?ListeningQuestionGroup $group = null,
        ?ListeningQuestion $question = null,
        ?Collection $questions = null,
    ): array {
        $options = $this->resolveOptions($payload, $group);
        $choices = is_array($options['choices'] ?? null) ? $options['choices'] : [];
        $errors = [];

        if ($question === null) {
            if ($this->payloadIncludesMatchingOptions($payload) && $choices === []) {
                $errors[] = 'At least one matching option is required.';
            }

            if ($choices !== []) {
                $errors = array_merge($errors, $this->validateMatchingChoices($choices));
            }

            return $errors;
        }

        if ($choices === []) {
            $errors[] = 'Add matching options before saving questions.';
        } else {
            $errors = array_merge($errors, $this->validateMatchingChoices($choices));
        }

        $questionText = trim((string) ($payload['question_text'] ?? $question->question_text ?? ''));

        if ($questionText === '') {
            $errors[] = 'Question text is required.';
        }

        $choiceKeys = array_map(
            fn (array $choice): string => strtoupper((string) ($choice['key'] ?? '')),
            $choices,
        );
        $correct = $this->normalizeAnswers($payload['correct_answer'] ?? $question->correct_answer, 'letter');

        if ($correct === []) {
            return array_merge($errors, $this->validateCorrectAnswerPresence([]));
        }

        $answer = $correct[0];
        $value = strtoupper((string) ($answer['value'] ?? ''));

        if ($value !== '' && ! in_array($value, $choiceKeys, true)) {
            $errors[] = "Correct answer must be one of the saved option keys (".implode(', ', array_filter($choiceKeys)).').';
        }

        if (! ($options['allow_choice_reuse'] ?? false) && $questions !== null && $value !== '') {
            $used = $questions
                ->where('id', '!=', $question->id)
                ->flatMap(fn (ListeningQuestion $q) => $this->answerValues(is_array($q->correct_answer) ? $q->correct_answer : []))
                ->map('strtoupper')
                ->all();

            if (in_array($value, $used, true)) {
                $errors[] = "Choice \"{$value}\" is already used and reuse is disabled.";
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function resolveOptions(array $payload, ?ListeningQuestionGroup $group): array
    {
        $groupOptions = is_array($group?->options) ? $group->options : [];
        $payloadOptions = $payload['options'] ?? null;

        if (! is_array($payloadOptions)) {
            return $groupOptions;
        }

        if ($payloadOptions === []) {
            return $groupOptions;
        }

        $payloadChoices = is_array($payloadOptions['choices'] ?? null) ? $payloadOptions['choices'] : null;
        $payloadItems = is_array($payloadOptions['items'] ?? null) ? $payloadOptions['items'] : null;

        return [
            'items' => ($payloadItems !== null && $payloadItems !== [])
                ? $payloadItems
                : ($groupOptions['items'] ?? []),
            'choices' => ($payloadChoices !== null && $payloadChoices !== [])
                ? $payloadChoices
                : ($groupOptions['choices'] ?? []),
            'allow_choice_reuse' => (bool) (
                $payloadOptions['allow_choice_reuse']
                ?? $groupOptions['allow_choice_reuse']
                ?? config('listening.question_types.matching.allow_choice_reuse_default', false)
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function normalizeMatchingOptions(array $options, ?ListeningQuestionGroup $group): array
    {
        $normalized = [
            'items' => array_values(array_map(fn (array $item): array => [
                'key' => trim((string) ($item['key'] ?? '')),
                'text' => trim((string) ($item['text'] ?? $item['label'] ?? '')),
            ], $options['items'] ?? [])),
            'choices' => array_values(array_map(fn (array $choice): array => [
                'key' => strtoupper(trim((string) ($choice['key'] ?? ''))),
                'text' => trim((string) ($choice['text'] ?? $choice['label'] ?? '')),
            ], $options['choices'] ?? [])),
            'allow_choice_reuse' => (bool) ($options['allow_choice_reuse'] ?? config('listening.question_types.matching.allow_choice_reuse_default', false)),
        ];

        if ($normalized['items'] === [] && $group !== null) {
            $group->loadMissing('questions');
            $derivedItems = $this->deriveItemsFromQuestions($group->questions);

            if ($derivedItems !== []) {
                $normalized['items'] = $derivedItems;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadIncludesMatchingOptions(array $payload): bool
    {
        if (! array_key_exists('options', $payload) || ! is_array($payload['options'])) {
            return false;
        }

        $options = $payload['options'];

        if ($options === []) {
            return false;
        }

        return array_key_exists('choices', $options) || array_key_exists('items', $options);
    }

    /**
     * @param  list<array<string, mixed>>  $choices
     * @return list<string>
     */
    private function validateMatchingChoices(array $choices): array
    {
        $errors = [];
        $keys = [];

        foreach ($choices as $index => $choice) {
            $key = strtoupper(trim((string) ($choice['key'] ?? '')));

            if ($key === '') {
                $errors[] = 'Option key is required at position '.($index + 1).'.';
            }

            if ($key !== '' && in_array($key, $keys, true)) {
                $errors[] = "Duplicate option key {$key}.";
            }

            $keys[] = $key;
        }

        return $errors;
    }

    /**
     * @param  Collection<int, ListeningQuestion>|null  $questions
     * @return list<array{key: string, text: string}>
     */
    private function deriveItemsFromQuestions(?Collection $questions): array
    {
        if ($questions === null || $questions->isEmpty()) {
            return [];
        }

        return $questions
            ->sortBy('question_number')
            ->map(fn (ListeningQuestion $question): array => [
                'key' => (string) $question->question_number,
                'text' => (string) ($question->question_text ?? ''),
            ])
            ->values()
            ->all();
    }

    public function buildPreviewData(ListeningQuestionGroup $group, Collection $questions): array
    {
        return [
            'type' => $this->type()->value,
            'instruction' => $group->instruction,
            'options' => $group->options ?? $this->defaultOptions(),
            'questions' => $questions->map(fn (ListeningQuestion $q) => [
                'number' => $q->question_number,
                'correct_answer' => $q->correct_answer,
            ])->values()->all(),
        ];
    }
}
