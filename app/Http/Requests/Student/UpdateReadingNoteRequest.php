<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use App\Enums\Exam\TestAttemptStatus;
use App\Models\ReadingNote;

class UpdateReadingNoteRequest extends ReadingAttemptScopedRequest
{
    public function authorize(): bool
    {
        if (! $this->ownsWritableAttempt()) {
            return false;
        }

        $note = $this->route('note');

        return $note instanceof ReadingNote
            && $note->attempt_id === $this->attemptFromRoute()?->id
            && $note->user_id === $this->user()?->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'content' => ['sometimes', 'required', 'string', 'max:20000'],
        ];
    }
}
