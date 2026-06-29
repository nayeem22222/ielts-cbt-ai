<?php

declare(strict_types=1);

namespace App\Http\Requests\Student\Listening;

use Illuminate\Foundation\Http\FormRequest;

class SyncListeningPlayerStateRequest extends FormRequest
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
            'current_section_number' => ['required', 'integer', 'min:1', 'max:4'],
            'current_question_number' => ['required', 'integer', 'min:1', 'max:40'],
            'visible_question_numbers' => ['nullable', 'array'],
            'visible_question_numbers.*' => ['integer', 'min:1', 'max:40'],
            'audio_state' => ['nullable', 'array'],
            'client_draft' => ['nullable', 'array'],
            'recover_answers' => ['nullable', 'array'],
        ];
    }
}
