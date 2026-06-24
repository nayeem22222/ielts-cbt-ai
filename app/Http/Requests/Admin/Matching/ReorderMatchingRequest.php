<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Matching;

use Illuminate\Validation\Rule;

class ReorderMatchingRequest extends MatchingQuestionRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $group = $this->matchingGroup();

        return [
            'option_ids' => ['nullable', 'array'],
            'option_ids.*' => [
                'integer',
                Rule::exists('reading_question_options', 'id')->where('group_id', $group?->id),
            ],
            'question_ids' => ['nullable', 'array'],
            'question_ids.*' => [
                'integer',
                Rule::exists('reading_questions', 'id')->where('group_id', $group?->id),
            ],
        ];
    }

    /**
     * @return array{option_ids?: list<int>, question_ids?: list<int>}
     */
    public function reorderPayload(): array
    {
        return array_filter([
            'option_ids' => $this->has('option_ids')
                ? array_map('intval', $this->input('option_ids', []))
                : null,
            'question_ids' => $this->has('question_ids')
                ? array_map('intval', $this->input('question_ids', []))
                : null,
        ], fn ($value) => $value !== null);
    }
}
