<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use App\Enums\Exam\ReadingHighlightColor;
use Illuminate\Validation\Rule;

class StoreReadingHighlightRequest extends ReadingAttemptScopedRequest
{
    public function authorize(): bool
    {
        return $this->ownsWritableAttempt();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'passage_id' => ['required', 'integer', 'min:1'],
            'selected_text' => ['required', 'string', 'max:5000'],
            'start_offset' => ['required', 'integer', 'min:0'],
            'end_offset' => ['required', 'integer', 'gt:start_offset'],
            'highlight_color' => ['required', 'string', Rule::in(ReadingHighlightColor::values())],
            'note_text' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
