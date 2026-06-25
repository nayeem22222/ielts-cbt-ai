<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Completion;

use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;
use App\Support\Reading\ReadingQuestionReferenceSupport;

class UpdateCompletionQuestionRequest extends CompletionScopedRequest
{
    protected function resolveGroup(): ?ReadingQuestionGroup
    {
        return $this->questionFromRoute()?->group;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge([
            'question_number' => ['nullable', 'integer', 'min:1', 'max:200'],
            'prompt' => ['nullable', 'string', 'max:10000'],
            'sentence_before' => ['nullable', 'string', 'max:5000'],
            'sentence_after' => ['nullable', 'string', 'max:5000'],
            'correct_answer' => ['required', 'string', 'max:500'],
            'alternative_answers' => ['nullable', 'array'],
            'alternative_answers.*' => ['string', 'max:500'],
            'case_sensitive' => ['nullable', 'boolean'],
            'explanation' => ['nullable', 'string', 'max:10000'],
            'difficulty' => ['nullable', 'string', 'max:20'],
        ], ReadingQuestionReferenceSupport::fullValidationRules());
    }

    /**
     * @return array<string, mixed>
     */
    public function questionAttributes(): array
    {
        $data = array_filter([
            'question_number' => $this->filled('question_number') ? (int) $this->input('question_number') : null,
            'prompt' => $this->input('prompt'),
            'sentence_before' => $this->input('sentence_before'),
            'sentence_after' => $this->input('sentence_after'),
            'correct_answer' => $this->string('correct_answer')->toString(),
            'alternative_answers' => $this->input('alternative_answers'),
            'case_sensitive' => $this->boolean('case_sensitive'),
            'explanation' => $this->input('explanation'),
            'difficulty' => $this->input('difficulty'),
        ], fn ($value) => $value !== null);

        foreach (ReadingQuestionReferenceSupport::attributeKeys() as $field) {
            if ($this->has($field)) {
                $data[$field] = $this->input($field);
            }
        }

        return $data;
    }
}
