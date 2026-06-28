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

class McqQuestionTypeService extends BaseListeningQuestionTypeService
{
    use HandlesMcqStyleOptions;

    public function type(): ListeningQuestionType
    {
        return ListeningQuestionType::MCQ;
    }

    public function label(): string
    {
        return 'Multiple Choice';
    }

    public function schema(): array
    {
        return [
            'default_layout' => ListeningLayoutType::Default->value,
            'default_answer_format' => ListeningAnswerFormat::Letter->value,
            'supports_options' => true,
            'required_group_fields' => ['options'],
            'required_question_fields' => ['correct_answer'],
        ];
    }

    public function defaultOptions(): ?array
    {
        return [
            ['key' => 'A', 'text' => '', 'is_correct' => false],
            ['key' => 'B', 'text' => '', 'is_correct' => false],
            ['key' => 'C', 'text' => '', 'is_correct' => false],
        ];
    }

    public function defaultSettings(): array
    {
        return [];
    }

    public function validationRules(): array
    {
        return ['options' => ['required', 'array', 'min:2']];
    }

    public function normalizePayload(array $payload, ?ListeningQuestionGroup $group = null, ?ListeningQuestion $question = null): array
    {
        if (isset($payload['options']) && is_array($payload['options']) && array_is_list($payload['options'])) {
            $payload['options'] = $this->normalizeMcqOptions($payload['options']);
        }

        if (isset($payload['correct_answer'])) {
            $payload['correct_answer'] = $this->normalizeAnswers($payload['correct_answer'], 'letter');
            $payload['answer_format'] = ListeningAnswerFormat::Letter->value;
        }

        if ($group === null && $question === null) {
            $payload['layout_type'] = $payload['layout_type'] ?? ListeningLayoutType::Default->value;
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
            $options = is_array($payload['options'] ?? null) && array_is_list($payload['options'] ?? [])
                ? $payload['options']
                : [];

            return $this->validateOptionKeys($options, 2);
        }

        if ($question !== null) {
            $options = is_array($group?->options) && array_is_list($group->options)
                ? $group->options
                : (is_array($payload['options'] ?? null) ? $payload['options'] : []);
            $correct = $this->normalizeAnswers($payload['correct_answer'] ?? $question->correct_answer, 'letter');

            return $this->validateMcqCorrectAnswer($options, $correct);
        }

        return [];
    }

    public function buildPreviewData(ListeningQuestionGroup $group, Collection $questions): array
    {
        return [
            'type' => $this->type()->value,
            'instruction' => $group->instruction,
            'options' => $group->options ?? [],
            'questions' => $questions->map(fn (ListeningQuestion $q) => [
                'number' => $q->question_number,
                'text' => $q->question_text,
                'correct_answer' => $q->correct_answer,
            ])->values()->all(),
        ];
    }
}
