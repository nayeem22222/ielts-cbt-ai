<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Diagram;

use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;

class UpdateDiagramQuestionRequest extends DiagramScopedRequest
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
        return [
            'question_number' => ['nullable', 'integer', 'min:1', 'max:200'],
            'label' => ['nullable', 'string', 'max:255'],
            'x' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'y' => ['nullable', 'numeric', 'min:0', 'max:100'],
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
        return array_filter([
            'question_number' => $this->filled('question_number') ? (int) $this->input('question_number') : null,
            'label' => $this->input('label'),
            'x' => $this->filled('x') ? (float) $this->input('x') : null,
            'y' => $this->filled('y') ? (float) $this->input('y') : null,
            'correct_answer' => $this->string('correct_answer')->toString(),
            'alternative_answers' => $this->input('alternative_answers'),
            'case_sensitive' => $this->boolean('case_sensitive'),
            'explanation' => $this->input('explanation'),
            'difficulty' => $this->input('difficulty'),
        ], fn ($value) => $value !== null);
    }

    protected function questionFromRoute(): ?ReadingQuestion
    {
        $question = $this->route('question');

        if ($question instanceof ReadingQuestion) {
            return $question;
        }

        if (is_numeric($question)) {
            return ReadingQuestion::query()->find((int) $question);
        }

        return null;
    }
}
