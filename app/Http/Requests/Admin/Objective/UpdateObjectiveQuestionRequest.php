<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Objective;

use App\Models\ReadingQuestionGroup;
use App\Support\Reading\ReadingQuestionReferenceSupport;

class UpdateObjectiveQuestionRequest extends ObjectiveScopedRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge([
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
            'reference_paragraph' => ['nullable', 'string', 'max:30'],
            'reference_start_offset' => ['nullable', 'integer', 'min:0'],
            'reference_end_offset' => ['nullable', 'integer', 'min:0'],
        ], ReadingQuestionReferenceSupport::validationRules());
    }

    /**
     * @return array<string, mixed>
     */
    public function questionAttributes(): array
    {
        $data = [];

        foreach ([
            'question_number',
            'prompt',
            'correct_answer',
            'correct_answers',
            'explanation',
            'difficulty',
            'options',
            'reference_paragraph',
            'reference_start_offset',
            'reference_end_offset',
            'reference_type',
            'reference_phrase',
            'reference_sentence',
        ] as $field) {
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
