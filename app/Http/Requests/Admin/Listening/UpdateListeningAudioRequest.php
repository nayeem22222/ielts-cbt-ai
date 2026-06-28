<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Listening;

use App\Models\Listening\ListeningAudio;
use Illuminate\Foundation\Http\FormRequest;

class UpdateListeningAudioRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ListeningAudio|null $audio */
        $audio = $this->route('audio');

        return $audio !== null && ($this->user()?->can('update', $audio) ?? false);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('meta') && is_string($this->input('meta'))) {
            $decoded = json_decode((string) $this->input('meta'), true);
            $this->merge(['meta' => is_array($decoded) ? $decoded : null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
