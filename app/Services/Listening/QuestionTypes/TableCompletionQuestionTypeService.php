<?php

declare(strict_types=1);

namespace App\Services\Listening\QuestionTypes;

use App\Enums\Listening\ListeningAnswerFormat;
use App\Enums\Listening\ListeningLayoutType;
use App\Enums\Listening\ListeningQuestionType;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use Illuminate\Database\Eloquent\Collection;

class TableCompletionQuestionTypeService extends BaseListeningQuestionTypeService
{
    public function type(): ListeningQuestionType
    {
        return ListeningQuestionType::TableCompletion;
    }

    public function label(): string
    {
        return 'Table Completion';
    }

    public function schema(): array
    {
        return [
            'default_layout' => ListeningLayoutType::Table->value,
            'default_answer_format' => ListeningAnswerFormat::Text->value,
            'supports_template' => true,
            'required_group_fields' => ['settings'],
        ];
    }

    public function defaultOptions(): ?array
    {
        return null;
    }

    public function defaultSettings(): array
    {
        return [
            'columns' => ['Column 1', 'Column 2'],
            'rows' => [
                ['cells' => [['text' => ''], ['blank' => 1]]],
            ],
            'word_limit' => 2,
        ];
    }

    public function validationRules(): array
    {
        return [
            'settings.columns' => ['required', 'array', 'min:1'],
            'settings.rows' => ['required', 'array', 'min:1'],
        ];
    }

    public function normalizePayload(array $payload, ?ListeningQuestionGroup $group = null, ?ListeningQuestion $question = null): array
    {
        $payload['settings'] = array_merge($this->defaultSettings(), is_array($payload['settings'] ?? null) ? $payload['settings'] : []);
        $payload['layout_type'] = $payload['layout_type'] ?? ListeningLayoutType::Table->value;

        if (isset($payload['correct_answer'])) {
            $payload['correct_answer'] = $this->normalizeAnswers($payload['correct_answer'], 'text');
            $payload['answer_format'] = ListeningAnswerFormat::Text->value;
        }

        return $payload;
    }

    /**
     * @return list<int>
     */
    protected function extractTableBlanks(array $settings): array
    {
        $blanks = [];

        foreach ($settings['rows'] ?? [] as $row) {
            foreach ($row['cells'] ?? [] as $cell) {
                if (isset($cell['blank'])) {
                    $blanks[] = (int) $cell['blank'];
                }
            }
        }

        return $blanks;
    }

    public function validatePayload(
        array $payload,
        ?ListeningQuestionGroup $group = null,
        ?ListeningQuestion $question = null,
        ?Collection $questions = null,
    ): array {
        if ($question === null) {
            $settings = is_array($payload['settings'] ?? null)
                ? $payload['settings']
                : (is_array($group?->settings) ? $group->settings : []);
            $errors = [];

            if (($settings['columns'] ?? []) === []) {
                $errors[] = 'Table columns are required.';
            }

            if (($settings['rows'] ?? []) === []) {
                $errors[] = 'Table rows are required.';
            }

            $blanks = $this->extractTableBlanks($settings);

            if ($group !== null) {
                $errors = array_merge(
                    $errors,
                    $this->validateTemplateBlanks($blanks, (int) $group->start_question_number, (int) $group->end_question_number),
                );
            }

            return $errors;
        }

        return $this->validateCorrectAnswerPresence(
            $this->normalizeAnswers($payload['correct_answer'] ?? $question->correct_answer, 'text'),
        );
    }

    public function buildPreviewData(ListeningQuestionGroup $group, Collection $questions): array
    {
        return [
            'type' => $this->type()->value,
            'instruction' => $group->instruction,
            'settings' => $group->settings ?? $this->defaultSettings(),
            'questions' => $questions->map(fn (ListeningQuestion $q) => [
                'number' => $q->question_number,
                'correct_answer' => $q->correct_answer,
            ])->values()->all(),
        ];
    }
}
