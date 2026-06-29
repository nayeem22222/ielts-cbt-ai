<?php

declare(strict_types=1);

namespace App\Http\Requests\Student\Listening;

use Illuminate\Foundation\Http\FormRequest;

class EndListeningAudioRequest extends FormRequest
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
            'section_number' => ['required', 'integer', 'min:1', 'max:4'],
            'position' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
