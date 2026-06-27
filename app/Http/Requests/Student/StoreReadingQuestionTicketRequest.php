<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use App\Enums\Exam\ReadingQuestionTicketIssueType;
use App\Enums\Exam\TestAttemptStatus;
use Illuminate\Validation\Rule;

class StoreReadingQuestionTicketRequest extends ReadingAttemptScopedRequest
{
    public function authorize(): bool
    {
        $attempt = $this->attemptFromRoute();

        return $attempt !== null
            && $this->user()?->id === $attempt->user_id
            && in_array($attempt->status, [TestAttemptStatus::InProgress, TestAttemptStatus::Submitted, TestAttemptStatus::Completed], true);
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
