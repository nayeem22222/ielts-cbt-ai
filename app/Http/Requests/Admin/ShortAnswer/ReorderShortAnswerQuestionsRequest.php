<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\ShortAnswer;

use App\Models\ReadingQuestionGroup;

class ReorderShortAnswerQuestionsRequest extends ShortAnswerScopedRequest
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
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['integer', 'min:1'],
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
