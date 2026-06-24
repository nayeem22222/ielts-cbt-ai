<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Matching;

use App\Models\ReadingQuestionGroup;

class UpdateScopedMatchingQuestionRequest extends MatchingScopedRequest
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
            'reference_paragraph' => ['nullable', 'string', 'max:30'],
            'reference_start_offset' => ['nullable', 'integer', 'min:0'],
            'reference_end_offset' => ['nullable', 'integer', 'min:0'],
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

        if ($this->has('reference_paragraph')) {
            $data['reference_paragraph'] = $this->input('reference_paragraph');
        }

        if ($this->has('reference_start_offset')) {
            $data['reference_start_offset'] = $this->input('reference_start_offset');
        }

        if ($this->has('reference_end_offset')) {
            $data['reference_end_offset'] = $this->input('reference_end_offset');
        }

        if ($this->has('sort_order')) {
            $data['sort_order'] = $this->input('sort_order');
        }

        return $data;
    }

    protected function resolveGroup(): ?ReadingQuestionGroup
    {
        return $this->questionFromRoute()?->group;
    }
}
