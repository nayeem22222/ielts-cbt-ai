<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Listening;

use App\Models\Listening\ListeningAudio;
use Illuminate\Foundation\Http\FormRequest;

class RetryListeningAudioProcessingRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ListeningAudio|null $audio */
        $audio = $this->route('audio');

        return $audio !== null && ($this->user()?->can('retry', $audio) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'force' => $this->boolean('force'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'force' => ['nullable', 'boolean'],
        ];
    }
}
