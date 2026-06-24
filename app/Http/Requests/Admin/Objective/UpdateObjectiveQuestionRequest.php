<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Objective;

use App\Models\ReadingQuestionGroup;

class UpdateObjectiveQuestionRequest extends ObjectiveScopedRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'question_number' => ['sometimes', 'required', 'integer', 'min:1', 'max:200'],
            'prompt' => ['sometimes', 'required', 'string', 'max:10000'],
            'correct_answer' => ['nullable', 'string', 'max:50'],
            'correct_answers' => ['nullable', 'array', 'min:1'],
            'correct_answers.*' => ['string', 'max:50'],
            'explanation' => ['nullable', 'string', 'max:10000'],
            'difficulty' => ['nullable', 'string', 'max:20'],
            'options' => ['nullable', 'array', 'min:2'],
            'options.*.option_key' => ['nullable', 'string', 'max:50'],
            'options.*.option_label' => ['required_with:options', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function questionAttributes(): array
    {
        $data = [];

        foreach (['question_number', 'prompt', 'correct_answer', 'correct_answers', 'explanation', 'difficulty', 'options'] as $field) {
            if ($this->has($field)) {
                $data[$field] = $this->input($field);
            }
        }

        return $data;
    }

    protected function resolveGroup(): ?ReadingQuestionGroup
    {
        return $this->questionFromRoute()?->group;
    }
}
