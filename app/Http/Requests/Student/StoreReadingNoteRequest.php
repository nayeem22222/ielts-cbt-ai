<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreReadingNoteRequest extends FormRequest
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
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:20000'],
            'question_id' => ['nullable', 'integer', 'min:1'],
            'passage_id' => ['nullable', 'integer', 'min:1'],
            'selected_text' => ['nullable', 'string', 'max:5000'],
            'start_offset' => ['nullable', 'integer', 'min:0', 'required_with:end_offset'],
            'end_offset' => ['nullable', 'integer', 'min:1', 'required_with:start_offset', 'gt:start_offset'],
        ];
    }
}
