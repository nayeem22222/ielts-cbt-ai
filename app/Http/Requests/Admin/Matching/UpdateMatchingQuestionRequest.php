<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Matching;

class UpdateMatchingQuestionRequest extends MatchingQuestionRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'question_number' => ['sometimes', 'required', 'integer', 'min:1', 'max:200'],
            'prompt' => ['sometimes', 'required', 'string', 'max:10000'],
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
        $data = [];

        if ($this->has('question_number')) {
            $data['question_number'] = (int) $this->input('question_number');
        }

        if ($this->has('prompt')) {
            $data['prompt'] = $this->string('prompt')->toString();
        }

        if ($this->has('paragraph_reference')) {
            $data['paragraph_reference'] = $this->input('paragraph_reference');
        }

        if ($this->has('correct_answer')) {
            $data['correct_answer'] = $this->input('correct_answer');
        }

        if ($this->has('explanation')) {
            $data['explanation'] = $this->input('explanation');
        }

        if ($this->has('sort_order')) {
            $data['sort_order'] = $this->input('sort_order');
        }

        return $data;
    }
}
