<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Listening;

use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Models\Listening\ListeningTranscript;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AttachListeningTranscriptRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ListeningTranscript|null $transcript */
        $transcript = ListeningTranscript::query()->find($this->input('transcript_id'));

        return $transcript !== null && ($this->user()?->can('attachToSection', $transcript) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'force_attach' => $this->boolean('force_attach'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'transcript_id' => ['required', 'integer', 'exists:listening_transcripts,id'],
            'force_attach' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var ListeningTest|null $test */
            $test = $this->route('listeningTest');
            /** @var ListeningSection|null $section */
            $section = $this->route('section');

            if ($test === null || $section === null) {
                return;
            }

            if ((int) $section->listening_test_id !== (int) $test->id) {
                $validator->errors()->add('section', 'Section does not belong to this test.');
            }

            $transcript = ListeningTranscript::query()->find($this->input('transcript_id'));

            if ($transcript === null) {
                $validator->errors()->add('transcript_id', 'Transcript not found.');

                return;
            }

            if (
                $section->audio_id !== null
                && $transcript->listening_audio_id !== null
                && (int) $section->audio_id !== (int) $transcript->listening_audio_id
                && config('listening.transcript.strict_audio_match', true)
                && ! $this->boolean('force_attach')
            ) {
                $validator->errors()->add('transcript_id', 'Transcript audio does not match section audio.');
            }

            if (
                $this->boolean('force_attach')
                && ! ($this->user()?->can('forceAttach', ListeningTranscript::class) ?? false)
            ) {
                $validator->errors()->add('force_attach', 'Unauthorized action.');
            }
        });
    }
}
