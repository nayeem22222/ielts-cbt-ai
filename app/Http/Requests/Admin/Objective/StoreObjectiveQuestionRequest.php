<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Objective;

use App\Support\Reading\ReadingQuestionReferenceSupport;

class StoreObjectiveQuestionRequest extends ObjectiveQuestionRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge([
            'question_number' => ['required', 'integer', 'min:1', 'max:200'],
            'prompt' => ['required', 'string', 'max:10000'],
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
        return [
            'question_number' => (int) $this->input('question_number'),
            'prompt' => $this->string('prompt')->toString(),
            'correct_answer' => $this->input('correct_answer'),
            'correct_answers' => $this->input('correct_answers'),
            'explanation' => $this->input('explanation'),
            'difficulty' => $this->input('difficulty', 'medium'),
            'options' => $this->input('options'),
            'reference_paragraph' => $this->input('reference_paragraph'),
            'reference_start_offset' => $this->input('reference_start_offset'),
            'reference_end_offset' => $this->input('reference_end_offset'),
            'reference_type' => $this->input('reference_type'),
            'reference_phrase' => $this->input('reference_phrase'),
            'reference_sentence' => $this->input('reference_sentence'),
        ];
    }
}
