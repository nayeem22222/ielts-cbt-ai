<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\ShortAnswer;

use App\Enums\Exam\ReadingCompletionAnswerRule;
use App\Models\ReadingQuestionGroup;
use Illuminate\Validation\Rule;

class StoreShortAnswerQuestionRequest extends ShortAnswerScopedRequest
{
    protected function resolveGroup(): ?ReadingQuestionGroup
    {
        return $this->groupFromRoute();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'answer_rule' => ['required', 'string', Rule::enum(ReadingCompletionAnswerRule::class)],
            'custom_answer_rule' => ['nullable', 'string', 'max:500'],
            'question_number' => ['required', 'integer', 'min:1', 'max:200'],
            'prompt' => ['required', 'string', 'max:10000'],
            'correct_answer' => ['required', 'string', 'max:500'],
            'alternative_answers' => ['nullable', 'array'],
            'alternative_answers.*' => ['string', 'max:500'],
            'case_sensitive' => ['nullable', 'boolean'],
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
            'answer_rule' => $this->string('answer_rule')->toString(),
            'custom_answer_rule' => $this->input('custom_answer_rule'),
            'question_number' => (int) $this->input('question_number'),
            'prompt' => $this->string('prompt')->toString(),
            'correct_answer' => $this->string('correct_answer')->toString(),
            'alternative_answers' => $this->input('alternative_answers'),
            'case_sensitive' => $this->boolean('case_sensitive'),
            'explanation' => $this->input('explanation'),
            'difficulty' => $this->input('difficulty'),
        ];
    }
}
