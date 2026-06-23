<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use App\Models\TestAttempt;
use Illuminate\Foundation\Http\FormRequest;

class AutosaveReadingAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attempt = $this->route('attempt');

        return $attempt instanceof TestAttempt
            && $this->user()?->id === $attempt->user_id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'current_section_id' => ['nullable', 'integer', 'exists:test_sections,id'],
            'active_question_id' => ['nullable', 'integer'],
            'time_remaining_seconds' => ['nullable', 'integer', 'min:0'],
            'answers' => ['nullable', 'array'],
            'answers.*.question_id' => ['required', 'integer', 'exists:questions,id'],
            'answers.*.answer_text' => ['nullable', 'string', 'max:5000'],
            'answers.*.selected_options' => ['nullable', 'array'],
            'answers.*.is_flagged' => ['nullable', 'boolean'],
            'highlights' => ['nullable', 'array'],
            'notes' => ['nullable', 'array'],
            'question_timings' => ['nullable', 'array'],
            'question_timings.*.question_id' => ['required', 'integer', 'exists:questions,id'],
            'question_timings.*.time_spent_seconds' => ['nullable', 'integer', 'min:0'],
            'question_timings.*.visit_count' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
