<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Exam\OfficialReadingQuestionType;
use App\Enums\Exam\PassageStatus;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingTest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class ReadingQuestionGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $test = $this->route('readingTest');

        return $test instanceof ReadingTest
            && ($this->user()?->can('update', $test) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'instruction' => ['nullable', 'string'],
            'question_type' => ['required', 'string', Rule::in(OfficialReadingQuestionType::values())],
            'start_question' => ['required', 'integer', 'min:1', 'max:100'],
            'end_question' => ['required', 'integer', 'min:1', 'max:100', 'gt:start_question'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'string', Rule::in(PassageStatus::values())],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function groupAttributes(): array
    {
        return [
            'title' => $this->string('title')->toString(),
            'instruction' => $this->input('instruction'),
            'question_type' => $this->string('question_type')->toString(),
            'start_question' => (int) $this->input('start_question'),
            'end_question' => (int) $this->input('end_question'),
            'sort_order' => $this->input('sort_order'),
            'status' => $this->string('status')->toString(),
            'settings' => [],
        ];
    }

    protected function currentPassage(): ?ReadingPassage
    {
        $passage = $this->route('passage');

        if ($passage instanceof ReadingPassage) {
            return $passage;
        }

        if (is_numeric($passage)) {
            return ReadingPassage::query()->find((int) $passage);
        }

        return null;
    }

    protected function currentGroup(): ?ReadingQuestionGroup
    {
        $group = $this->route('group');

        if ($group instanceof ReadingQuestionGroup) {
            return $group;
        }

        if (is_numeric($group)) {
            return ReadingQuestionGroup::query()->find((int) $group);
        }

        return null;
    }
}
