<?php

declare(strict_types=1);

namespace App\Http\Requests\Student\Listening;

use Illuminate\Foundation\Http\FormRequest;

class BulkSaveListeningAnswersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer', 'exists:listening_questions,id'],
            'answers.*.student_answer' => ['nullable'],
            'current_section_number' => ['nullable', 'integer', 'min:1', 'max:4'],
            'current_question_number' => ['nullable', 'integer', 'min:1', 'max:40'],
        ];
    }
}
