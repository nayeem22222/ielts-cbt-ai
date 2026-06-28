<x-ui.select name="audio_id" label="Audio">
    <option value="">No audio selected</option>
    @foreach ($audios as $audio)
        @php
            $isReady = $audio->processing_status === \App\Enums\Listening\ListeningAudioProcessingStatus::Completed
                && $audio->validation_status === \App\Enums\Listening\ListeningAudioValidationStatus::Valid;
        @endphp
        <option
            value="{{ $audio->id }}"
            @selected((string) old('audio_id', $section->audio_id ?? '') === (string) $audio->id)
            @disabled(! $isReady)
        >
            {{ $audio->original_name }}
            @if ($audio->duration_seconds)
                · {{ $audio->duration_seconds }}s
            @endif
            · {{ $audio->processing_status?->label() ?? $audio->processing_status }}
            · {{ $audio->validation_status?->label() ?? $audio->validation_status }}
            @unless ($isReady)
                · Not ready
            @endunless
        </option>
    @endforeach
</x-ui.select>
<p class="mt-1 text-xs aa-muted">Only processed and valid audio can be selected. Upload and process audio from the Listening Audio library first.</p>
