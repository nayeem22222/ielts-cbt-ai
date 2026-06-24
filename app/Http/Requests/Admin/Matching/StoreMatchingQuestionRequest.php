<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Matching;

class StoreMatchingQuestionRequest extends MatchingQuestionRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'question_number' => ['required', 'integer', 'min:1', 'max:200'],
            'prompt' => ['required', 'string', 'max:10000'],
            'paragraph_reference' => ['nullable', 'string', 'max:30'],
            'correct_answer' => ['nullable', 'string', 'max:50'],
            'explanation' => ['nullable', 'string', 'max:10000'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
        ];
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
            'sort_order' => $this->input('sort_order'),
        ];
    }
}
