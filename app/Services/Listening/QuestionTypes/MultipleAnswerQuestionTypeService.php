<?php

declare(strict_types=1);

namespace App\Services\Listening\QuestionTypes;

use App\Enums\Listening\ListeningAnswerFormat;
use App\Enums\Listening\ListeningLayoutType;
use App\Enums\Listening\ListeningQuestionType;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Services\Listening\QuestionTypes\Concerns\HandlesMcqStyleOptions;
use Illuminate\Database\Eloquent\Collection;

class MultipleAnswerQuestionTypeService extends BaseListeningQuestionTypeService
{
    use HandlesMcqStyleOptions;

    public function type(): ListeningQuestionType
    {
        return ListeningQuestionType::MultipleAnswer;
    }

    public function label(): string
    {
        return 'Multiple Answer';
    }

    public function schema(): array
    {
        return [
            'default_layout' => ListeningLayoutType::Default->value,
            'default_answer_format' => ListeningAnswerFormat::Multiple->value,
            'supports_options' => true,
            'supports_multiple_answers' => true,
            'required_group_fields' => ['options', 'settings'],
        ];
    }

    public function defaultOptions(): ?array
    {
        return [
            ['key' => 'A', 'text' => ''],
            ['key' => 'B', 'text' => ''],
            ['key' => 'C', 'text' => ''],
        ];
    }

    public function defaultSettings(): array
    {
        return [
            'required_answers' => 2,
            'display_instruction' => 'Choose TWO letters, A-E.',
            'partial_marking' => config('listening.question_types.multiple_answer.allow_partial_marking', false),
        ];
    }

    public function validationRules(): array
    {
        return [
            'options' => ['required', 'array', 'min:3'],
            'settings.required_answers' => ['required', 'integer', 'min:1'],
        ];
    }

    public function normalizePayload(array $payload, ?ListeningQuestionGroup $group = null, ?ListeningQuestion $question = null): array
    {
        if (isset($payload['options']) && is_array($payload['options']) && array_is_list($payload['options'])) {
            $payload['options'] = array_values(array_map(fn (array $o): array => [
                'key' => strtoupper(trim((string) ($o['key'] ?? ''))),
                'text' => trim((string) ($o['text'] ?? '')),
            ], $payload['options']));
        }

        $payload['settings'] = array_merge($this->defaultSettings(), is_array($payload['settings'] ?? null) ? $payload['settings'] : []);

        if (isset($payload['correct_answer'])) {
            $payload['correct_answer'] = $this->normalizeAnswers($payload['correct_answer'], 'letter');
            $payload['answer_format'] = ListeningAnswerFormat::Multiple->value;
            $payload['order_sensitive'] = $payload['order_sensitive'] ?? false;
        }

        return $payload;
    }

    public function validatePayload(
        array $payload,
        ?ListeningQuestionGroup $group = null,
        ?ListeningQuestion $question = null,
        ?Collection $questions = null,
    ): array {
        if ($question === null && $group === null) {
            $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
            $errors = $this->validateOptionKeys($options, 3);
            $required = (int) ($payload['settings']['required_answers'] ?? 0);

            if ($required < 1) {
                $errors[] = 'Required answers count must be at least 1.';
            }

            return $errors;
        }

        if ($question !== null) {
            $options = is_array($group?->options) ? $group->options : [];
            $settings = is_array($group?->settings) ? $group->settings : [];
            $required = (int) ($settings['required_answers'] ?? 1);
            $correct = $this->normalizeAnswers($payload['correct_answer'] ?? $question->correct_answer, 'letter');
            $errors = $this->validateCorrectAnswerPresence($correct);

            if (count($this->answerValues($correct)) !== $required) {
                $errors[] = "Exactly {$required} correct answers are required.";
            }

            $keys = $this->optionKeysFromList($options);

            foreach ($this->answerValues($correct) as $value) {
                if (! in_array(strtoupper($value), array_map('strtoupper', $keys), true)) {
                    $errors[] = "Correct answer \"{$value}\" does not match any option key.";
                }
            }

            return $errors;
        }

        return [];
    }

    public function buildPreviewData(ListeningQuestionGroup $group, Collection $questions): array
    {
        return [
            'type' => $this->type()->value,
            'instruction' => $group->instruction,
            'settings' => $group->settings ?? $this->defaultSettings(),
            'options' => $group->options ?? [],
            'questions' => $questions->map(fn (ListeningQuestion $q) => [
                'number' => $q->question_number,
                'correct_answer' => $q->correct_answer,
            ])->values()->all(),
        ];
    }
}
