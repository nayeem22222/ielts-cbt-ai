<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use App\Enums\Exam\TestAttemptStatus;
use App\Models\ReadingAttempt;
use Illuminate\Foundation\Http\FormRequest;

class SaveReadingPositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attempt = $this->route('attempt');

        return $attempt instanceof ReadingAttempt
            && $this->user()?->id === $attempt->user_id
            && $attempt->status === TestAttemptStatus::InProgress;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'current_passage' => ['required', 'integer', 'exists:reading_passages,id'],
            'current_question' => ['required', 'integer', 'exists:reading_questions,id'],
        ];
    }
}
