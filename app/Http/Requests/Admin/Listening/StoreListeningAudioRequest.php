<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Listening;

use App\Models\Listening\ListeningAudio;
use App\Rules\Listening\ValidListeningAudioFile;
use Illuminate\Foundation\Http\FormRequest;

class StoreListeningAudioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ListeningAudio::class) ?? false;
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
        $maxKb = (int) config('listening.audio.max_file_size_mb', 100) * 1024;

        return [
            'audio_file' => ['required', 'file', new ValidListeningAudioFile, 'max:'.$maxKb],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
