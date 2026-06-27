<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Listening;

use App\Enums\Listening\ListeningTranscriptSourceType;
use App\Enums\Listening\ListeningTranscriptVisibility;
use App\Models\Listening\ListeningTranscript;
use App\Rules\Listening\ValidTimestampedTranscript;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreListeningTranscriptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ListeningTranscript::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('listening_audio_id') === '') {
            $this->merge(['listening_audio_id' => null]);
        }

        $this->merge([
            'is_official' => $this->boolean('is_official'),
        ]);

        if ($this->has('timestamped_transcript') && is_string($this->input('timestamped_transcript'))) {
            $decoded = json_decode((string) $this->input('timestamped_transcript'), true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['timestamped_transcript' => $decoded]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $audioId = $this->input('listening_audio_id') !== null ? (int) $this->input('listening_audio_id') : null;

        return [
            'listening_audio_id' => ['nullable', 'integer', 'exists:listening_audios,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'passage_title' => ['nullable', 'string', 'max:255'],
            'passage_note' => ['nullable', 'string'],
            'transcript_text' => ['required', 'string', 'min:10', 'max:'.config('listening.transcript.max_text_length', 100000)],
            'formatted_transcript' => ['nullable', 'string'],
            'timestamped_transcript' => ['nullable', 'array', new ValidTimestampedTranscript($audioId)],
            'language' => ['required', 'string', 'max:20'],
            'visibility' => ['required', 'string', Rule::enum(ListeningTranscriptVisibility::class)],
            'is_official' => ['boolean'],
            'source_type' => ['nullable', 'string', Rule::in(array_column(ListeningTranscriptSourceType::cases(), 'value'))],
            'meta' => ['nullable', 'array'],
            'return_url' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
