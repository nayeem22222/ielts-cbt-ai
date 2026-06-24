<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use App\Enums\Exam\TestAttemptStatus;
use App\Models\ReadingAttempt;
use App\Models\ReadingQuestion;
use Illuminate\Foundation\Http\FormRequest;

class ToggleReadingFlagRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attempt = $this->route('attempt');
        $question = $this->route('question');

        if (! $attempt instanceof ReadingAttempt || ! $question instanceof ReadingQuestion) {
            return false;
        }

        return $this->user()?->id === $attempt->user_id
            && $attempt->status === TestAttemptStatus::InProgress;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'flagged' => ['required', 'boolean'],
        ];
    }
}
