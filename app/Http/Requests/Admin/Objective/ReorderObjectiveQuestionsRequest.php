<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Objective;

use Illuminate\Validation\Rule;

class ReorderObjectiveQuestionsRequest extends ObjectiveQuestionRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $group = $this->objectiveGroup();

        return [
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => [
                'integer',
                Rule::exists('reading_questions', 'id')->where('group_id', $group?->id),
            ],
        ];
    }

    /**
     * @return list<int>
     */
    public function questionIds(): array
    {
        return array_map('intval', $this->input('question_ids', []));
    }
}
