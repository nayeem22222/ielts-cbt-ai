<?php

declare(strict_types=1);

namespace App\Http\Requests\Student\Listening;

use Illuminate\Foundation\Http\FormRequest;

class AutoSaveListeningBulkRequest extends FormRequest
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
            'answers.*.answer' => ['nullable'],
            'answers.*.client_answer_hash' => ['nullable', 'string', 'max:255'],
            'answers.*.client_sequence' => ['nullable', 'integer', 'min:0'],
            'answers.*.client_saved_at' => ['nullable', 'date'],
            'current_section_number' => ['nullable', 'integer', 'min:1', 'max:4'],
            'current_question_number' => ['nullable', 'integer', 'min:1', 'max:40'],
        ];
    }
}
