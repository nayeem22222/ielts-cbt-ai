<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Diagram;

use App\Enums\Exam\ReadingCompletionAnswerRule;
use App\Models\ReadingQuestionGroup;
use Illuminate\Validation\Rule;

class SaveDiagramLabelsRequest extends DiagramScopedRequest
{
    protected function resolveGroup(): ?ReadingQuestionGroup
    {
        return $this->groupFromRoute();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'answer_rule' => ['required', 'string', Rule::enum(ReadingCompletionAnswerRule::class)],
            'custom_answer_rule' => ['nullable', 'string', 'max:500'],
            'confirm_remove' => ['nullable', 'boolean'],
            'labels' => ['required', 'array', 'min:1'],
            'labels.*.question_number' => ['required', 'integer', 'min:1', 'max:200'],
            'labels.*.x' => ['required', 'numeric', 'min:0', 'max:100'],
            'labels.*.y' => ['required', 'numeric', 'min:0', 'max:100'],
            'labels.*.label' => ['nullable', 'string', 'max:255'],
            'labels.*.correct_answer' => ['nullable', 'string', 'max:500'],
            'labels.*.alternative_answers' => ['nullable', 'array'],
            'labels.*.alternative_answers.*' => ['string', 'max:500'],
            'labels.*.case_sensitive' => ['nullable', 'boolean'],
            'labels.*.explanation' => ['nullable', 'string', 'max:10000'],
            'labels.*.difficulty' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function labelAttributes(): array
    {
        return [
            'answer_rule' => $this->string('answer_rule')->toString(),
            'custom_answer_rule' => $this->input('custom_answer_rule'),
            'labels' => $this->input('labels', []),
            'confirm_remove' => $this->boolean('confirm_remove'),
        ];
    }
}
