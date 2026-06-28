<?php

declare(strict_types=1);

namespace App\Services\Listening\QuestionTypes;

use App\Enums\Listening\ListeningAnswerFormat;
use App\Enums\Listening\ListeningLayoutType;
use App\Enums\Listening\ListeningQuestionType;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use Illuminate\Database\Eloquent\Collection;

class FlowchartCompletionQuestionTypeService extends BaseListeningQuestionTypeService
{
    public function type(): ListeningQuestionType
    {
        return ListeningQuestionType::FlowchartCompletion;
    }

    public function label(): string
    {
        return 'Flowchart Completion';
    }

    public function schema(): array
    {
        return [
            'default_layout' => ListeningLayoutType::Flowchart->value,
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
            'steps' => [
                ['order' => 1, 'text' => 'Step 1'],
                ['order' => 2, 'blank' => 1],
            ],
            'word_limit' => 2,
            'direction' => 'vertical',
        ];
    }

    public function validationRules(): array
    {
        return ['settings.steps' => ['required', 'array', 'min:1']];
    }

    public function normalizePayload(array $payload, ?ListeningQuestionGroup $group = null, ?ListeningQuestion $question = null): array
    {
        $payload['settings'] = array_merge($this->defaultSettings(), is_array($payload['settings'] ?? null) ? $payload['settings'] : []);
        $payload['layout_type'] = $payload['layout_type'] ?? ListeningLayoutType::Flowchart->value;

        if (isset($payload['correct_answer'])) {
            $payload['correct_answer'] = $this->normalizeAnswers($payload['correct_answer'], 'text');
            $payload['answer_format'] = ListeningAnswerFormat::Text->value;
        }

        return $payload;
    }

    /**
     * @return list<int>
     */
    protected function extractFlowchartBlanks(array $settings): array
    {
        $blanks = [];

        foreach ($settings['steps'] ?? [] as $step) {
            if (isset($step['blank'])) {
                $blanks[] = (int) $step['blank'];
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
            $blanks = $this->extractFlowchartBlanks($settings);
            $errors = [];

            if ($blanks === []) {
                $errors[] = 'At least one flowchart step must be a blank.';
            }

            $direction = (string) ($settings['direction'] ?? 'vertical');

            if (! in_array($direction, ['vertical', 'horizontal'], true)) {
                $errors[] = 'Flowchart direction must be vertical or horizontal.';
            }

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
