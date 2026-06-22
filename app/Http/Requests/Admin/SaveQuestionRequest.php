<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Course\PublishStatus;
use App\Enums\Exam\ReadingQuestionType;
use App\Models\ExamTest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $test = $this->route('reading_test');

        return $test instanceof ExamTest
            && ($this->user()?->can('update', $test) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(ReadingQuestionType::values())],
            'question_number' => ['required', 'integer', 'min:1', 'max:999'],
            'prompt' => ['required', 'string', 'max:10000'],
            'stimulus' => ['nullable', 'string', 'max:10000'],
            'marks' => ['nullable', 'numeric', 'min:0.5', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
            'difficulty' => ['nullable', 'string', Rule::in(['easy', 'medium', 'hard'])],
            'status' => ['nullable', 'string', Rule::in(PublishStatus::values())],
            'options' => ['nullable', 'array'],
            'options.*' => ['nullable', 'string', 'max:2000'],
            'correct_answer' => ['nullable'],
            'answer_json' => ['nullable', 'array'],
            'explanation' => ['nullable', 'string', 'max:5000'],
            'rationale' => ['nullable', 'string', 'max:5000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
        ];
    }
}
