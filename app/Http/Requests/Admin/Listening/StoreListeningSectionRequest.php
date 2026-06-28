<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Listening;

use App\Enums\Listening\ListeningSectionType;
use App\Models\Listening\ListeningAudio;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Repositories\Listening\ListeningSectionRepository;
use App\Support\Listening\ListeningSectionMap;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreListeningSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ListeningTest|null $test */
        $test = $this->route('listeningTest');

        return $test !== null && ($this->user()?->can('create', [ListeningSection::class, $test]) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $nullableIntegers = ['audio_id', 'transcript_id', 'display_order', 'duration_seconds', 'preparation_seconds'];

        foreach ($nullableIntegers as $field) {
            if ($this->input($field) === '' || $this->input($field) === null) {
                $this->merge([$field => null]);
            }
        }

        $this->merge([
            'is_active' => $this->boolean('is_active', true),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var ListeningTest $test */
        $test = $this->route('listeningTest');

        return [
            'section_number' => [
                'required',
                'integer',
                Rule::in(ListeningSectionMap::officialSectionNumbers()),
                Rule::unique('listening_sections', 'section_number')
                    ->where(fn ($query) => $query->where('listening_test_id', $test->id)),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'instruction' => ['nullable', 'string'],
            'section_type' => ['required', 'string', Rule::in(ListeningSectionType::values())],
            'audio_id' => ['nullable', 'integer', 'exists:listening_audios,id'],
            'transcript_id' => ['nullable', 'integer', 'exists:listening_transcripts,id'],
            'display_order' => ['nullable', 'integer', 'min:1', 'max:4'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:3600'],
            'preparation_seconds' => ['nullable', 'integer', 'min:0', 'max:300'],
            'is_active' => ['boolean'],
            'meta' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var ListeningTest $test */
            $test = $this->route('listeningTest');
            $repository = app(ListeningSectionRepository::class);
            $sectionNumber = (int) $this->input('section_number');

            if ($sectionNumber > 0 && $repository->sectionNumberExists($test, $sectionNumber)) {
                $validator->errors()->add(
                    'section_number',
                    'Section number already exists for this listening test.',
                );
            }

            if ($this->boolean('is_active', true)) {
                if ($repository->countActiveSections($test) >= 4) {
                    $validator->errors()->add('section_number', 'Maximum 4 sections are allowed.');
                }
            }

            $audioId = $this->integer('audio_id') ?: null;

            if ($audioId !== null) {
                $audio = ListeningAudio::query()->find($audioId);

                if ($audio !== null && ! $this->audioIsReadyForSections($audio)) {
                    $validator->errors()->add('audio_id', 'Selected audio is not ready. Upload and process audio first.');
                }
            }
        });
    }

    private function audioIsReadyForSections(ListeningAudio $audio): bool
    {
        $playablePath = $audio->playablePath();

        return $audio->processing_status === \App\Enums\Listening\ListeningAudioProcessingStatus::Completed
            && $audio->validation_status === \App\Enums\Listening\ListeningAudioValidationStatus::Valid
            && $audio->duration_seconds !== null
            && $playablePath !== null
            && \Illuminate\Support\Facades\Storage::disk($audio->disk ?: (string) config('listening.audio.disk', 'public'))->exists($playablePath);
    }
}
