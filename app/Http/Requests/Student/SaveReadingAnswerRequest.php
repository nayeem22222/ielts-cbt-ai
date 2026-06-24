<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use App\Enums\Exam\OfficialReadingQuestionType;
use App\Enums\Exam\TestAttemptStatus;
use App\Models\ReadingAttempt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveReadingAnswerRequest extends FormRequest
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
            'question_id' => ['required', 'integer', 'exists:reading_questions,id'],
            'question_number' => ['required', 'integer', 'min:1'],
            'question_type' => ['required', 'string', Rule::in(array_column(OfficialReadingQuestionType::cases(), 'value'))],
            'passage_id' => ['required', 'integer', 'exists:reading_passages,id'],
            'group_id' => ['required', 'integer', 'exists:reading_question_groups,id'],
            'answer' => ['nullable', 'string', 'max:5000'],
            'answer_json' => ['nullable', 'array'],
            'answer_json.*' => ['string', 'max:500'],
        ];
    }
}
