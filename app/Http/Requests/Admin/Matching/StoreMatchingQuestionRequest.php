<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Matching;

use App\Support\Reading\ReadingQuestionReferenceSupport;

class StoreMatchingQuestionRequest extends MatchingQuestionRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge([
            'question_number' => ['required', 'integer', 'min:1', 'max:200'],
            'prompt' => ['required', 'string', 'max:10000'],
            'paragraph_reference' => ['nullable', 'string', 'max:30'],
            'correct_answer' => ['nullable', 'string', 'max:50'],
            'explanation' => ['nullable', 'string', 'max:10000'],
            'reference_paragraph' => ['nullable', 'string', 'max:30'],
            'reference_start_offset' => ['nullable', 'integer', 'min:0'],
            'reference_end_offset' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
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
            'paragraph_reference' => $this->input('paragraph_reference'),
            'correct_answer' => $this->input('correct_answer'),
            'explanation' => $this->input('explanation'),
            'reference_paragraph' => $this->input('reference_paragraph'),
            'reference_start_offset' => $this->input('reference_start_offset'),
            'reference_end_offset' => $this->input('reference_end_offset'),
            'reference_type' => $this->input('reference_type'),
            'reference_phrase' => $this->input('reference_phrase'),
            'reference_sentence' => $this->input('reference_sentence'),
            'sort_order' => $this->input('sort_order'),
        ];
    }
}
