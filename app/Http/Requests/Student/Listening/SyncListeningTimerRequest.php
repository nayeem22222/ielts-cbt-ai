<?php

declare(strict_types=1);

namespace App\Http\Requests\Student\Listening;

use Illuminate\Foundation\Http\FormRequest;

class SyncListeningTimerRequest extends FormRequest
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
            'client_remaining_seconds' => ['nullable', 'integer', 'min:0'],
            'client_phase' => ['nullable', 'string', 'max:50'],
            'client_server_offset_ms' => ['nullable', 'integer'],
        ];
    }
}
