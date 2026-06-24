<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Completion;

use App\Models\ReadingQuestionGroup;

class ReorderCompletionQuestionsRequest extends CompletionScopedRequest
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
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['integer', 'distinct'],
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
