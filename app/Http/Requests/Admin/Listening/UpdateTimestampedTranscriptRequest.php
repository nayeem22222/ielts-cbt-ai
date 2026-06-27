<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Listening;

use App\Models\Listening\ListeningTranscript;
use App\Rules\Listening\ValidTimestampedTranscript;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTimestampedTranscriptRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ListeningTranscript|null $transcript */
        $transcript = $this->route('transcript');

        return $transcript !== null && ($this->user()?->can('updateTimestamps', $transcript) ?? false);
    }

    protected function prepareForValidation(): void
    {
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
        /** @var ListeningTranscript $transcript */
        $transcript = $this->route('transcript');

        return [
            'timestamped_transcript' => [
                'required',
                'array',
                new ValidTimestampedTranscript($transcript->listening_audio_id),
            ],
            'timestamped_transcript.*.line' => ['required', 'integer', 'min:1'],
            'timestamped_transcript.*.speaker' => ['nullable', 'string', 'max:100'],
            'timestamped_transcript.*.start' => ['required', 'numeric', 'min:0'],
            'timestamped_transcript.*.end' => ['nullable', 'numeric', 'gte:timestamped_transcript.*.start'],
            'timestamped_transcript.*.text' => ['required', 'string', 'min:1'],
        ];
    }
}
