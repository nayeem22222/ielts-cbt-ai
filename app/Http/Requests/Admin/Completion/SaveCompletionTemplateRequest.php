<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Completion;

use App\Enums\Exam\ReadingCompletionAnswerRule;
use App\Models\ReadingQuestionGroup;
use Illuminate\Validation\Rule;

class SaveCompletionTemplateRequest extends CompletionScopedRequest
{
    protected function resolveGroup(): ?ReadingQuestionGroup
    {
        $group = $this->route('group');

        return $group instanceof ReadingQuestionGroup ? $group : null;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach (['table_data', 'flow_steps'] as $field) {
            $value = $this->input($field);

            if (is_string($value) && $value !== '') {
                $decoded = json_decode($value, true);
                $merge[$field] = is_array($decoded) ? $decoded : null;
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'answer_rule' => ['required', Rule::enum(ReadingCompletionAnswerRule::class)],
            'custom_answer_rule' => ['nullable', 'string', 'max:255', 'required_if:answer_rule,custom'],
            'template_html' => ['required', 'string', 'max:50000'],
            'table_data' => ['nullable', 'array'],
            'table_data.rows' => ['nullable', 'array'],
            'flow_steps' => ['nullable', 'array'],
            'confirm_remove' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function templateAttributes(): array
    {
        return [
            'answer_rule' => $this->string('answer_rule')->toString(),
            'custom_answer_rule' => $this->input('custom_answer_rule'),
            'template_html' => $this->string('template_html')->toString(),
            'table_data' => $this->input('table_data'),
            'flow_steps' => $this->input('flow_steps'),
            'confirm_remove' => $this->boolean('confirm_remove'),
        ];
    }
}
