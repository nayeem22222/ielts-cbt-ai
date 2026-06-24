<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use App\Enums\Exam\ReadingQuestionTicketIssueType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReadingQuestionTicketRequest extends FormRequest
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
            'question_id' => ['required', 'integer', 'min:1'],
            'issue_type' => ['required', 'string', Rule::in(ReadingQuestionTicketIssueType::values())],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }
}
