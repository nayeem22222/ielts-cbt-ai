<x-ui.select name="audio_id" label="Audio">
    <option value="">No audio selected</option>
    @foreach ($audios as $audio)
        <option value="{{ $audio->id }}" @selected((string) old('audio_id', $section->audio_id ?? '') === (string) $audio->id)>
            {{ $audio->original_name }}
            @if ($audio->duration_seconds)
                · {{ $audio->duration_seconds }}s
            @endif
            · {{ $audio->processing_status?->label() ?? $audio->processing_status }}
            · {{ $audio->validation_status?->label() ?? $audio->validation_status }}
        </option>
    @endforeach
</x-ui.select>
