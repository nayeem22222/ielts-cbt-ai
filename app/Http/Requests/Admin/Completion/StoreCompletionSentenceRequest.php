<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Completion;

use App\Models\ReadingQuestionGroup;
use App\Support\Reading\ReadingQuestionReferenceSupport;

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
        return array_merge([
            'question_number' => ['required', 'integer', 'min:1', 'max:200'],
            'prompt' => ['nullable', 'string', 'max:10000', 'required_without_all:sentence_before,sentence_after'],
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
        return [
            'question_number' => (int) $this->input('question_number'),
            'prompt' => $this->input('prompt'),
            'sentence_before' => $this->input('sentence_before'),
            'sentence_after' => $this->input('sentence_after'),
            'correct_answer' => $this->string('correct_answer')->toString(),
            'alternative_answers' => $this->input('alternative_answers'),
            'case_sensitive' => $this->boolean('case_sensitive'),
            'explanation' => $this->input('explanation'),
            'difficulty' => $this->input('difficulty', 'medium'),
            'reference_paragraph' => $this->input('reference_paragraph'),
            'reference_start_offset' => $this->input('reference_start_offset'),
            'reference_end_offset' => $this->input('reference_end_offset'),
            'reference_type' => $this->input('reference_type'),
            'reference_phrase' => $this->input('reference_phrase'),
            'reference_sentence' => $this->input('reference_sentence'),
        ];
    }
}
