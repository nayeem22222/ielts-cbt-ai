<x-ui.select name="transcript_id" label="Transcript">
    <option value="">No transcript selected</option>
    @foreach ($transcripts as $transcript)
        <option value="{{ $transcript->id }}" @selected((string) old('transcript_id', $section->transcript_id ?? '') === (string) $transcript->id)>
            {{ $transcript->title }}
            · {{ $transcript->visibility?->label() ?? $transcript->visibility }}
            · {{ $transcript->is_official ? 'Official' : 'Custom' }}
        </option>
    @endforeach
</x-ui.select>
