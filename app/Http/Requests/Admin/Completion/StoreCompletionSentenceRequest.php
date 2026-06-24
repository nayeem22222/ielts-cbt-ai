<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Completion;

use App\Models\ReadingQuestionGroup;

class StoreCompletionSentenceRequest extends CompletionScopedRequest
{
    protected function resolveGroup(): ?ReadingQuestionGroup
    {
        $group = $this->route('group');

        return $group instanceof ReadingQuestionGroup ? $group : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'question_number' => ['required', 'integer', 'min:1', 'max:200'],
            'prompt' => ['required', 'string', 'max:10000'],
            'correct_answer' => ['required', 'string', 'max:500'],
            'alternative_answers' => ['nullable', 'array'],
            'alternative_answers.*' => ['string', 'max:500'],
            'explanation' => ['nullable', 'string', 'max:10000'],
            'difficulty' => ['nullable', 'string', 'max:20'],
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
            'correct_answer' => $this->string('correct_answer')->toString(),
            'alternative_answers' => $this->input('alternative_answers'),
            'explanation' => $this->input('explanation'),
            'difficulty' => $this->input('difficulty', 'medium'),
        ];
    }
}
