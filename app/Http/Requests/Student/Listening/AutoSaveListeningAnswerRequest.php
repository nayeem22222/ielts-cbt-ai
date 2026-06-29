<?php

declare(strict_types=1);

namespace App\Http\Requests\Student\Listening;

use Illuminate\Foundation\Http\FormRequest;

class AutoSaveListeningAnswerRequest extends FormRequest
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
            'question_id' => ['required', 'integer', 'exists:listening_questions,id'],
            'answer' => ['nullable'],
            'client_answer_hash' => ['nullable', 'string', 'max:255'],
            'client_sequence' => ['nullable', 'integer', 'min:0'],
            'client_saved_at' => ['nullable', 'date'],
            'time_spent_seconds' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
