<div class="grid gap-4 md:grid-cols-2">
    <x-ui.input name="title" label="Title" :value="old('title', $transcript->title)" />
    <x-ui.select name="listening_audio_id" label="Listening Audio">
        <option value="">No audio linked</option>
        @foreach ($audios as $audio)
            <option value="{{ $audio->id }}" @selected((string) old('listening_audio_id', $transcript->listening_audio_id ?? '') === (string) $audio->id)>{{ $audio->original_name }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.input name="passage_title" label="Passage Title" :value="old('passage_title', $transcript->passage_title)" />
    <x-ui.select name="language" label="Language" required>
        <option value="en" @selected(old('language', $transcript->language ?? 'en') === 'en')>English (en)</option>
        <option value="bn" @selected(old('language', $transcript->language ?? '') === 'bn')>Bengali (bn)</option>
    </x-ui.select>
    <x-ui.select name="visibility" label="Visibility" required>
        @foreach ($visibilities as $visibility)
            <option value="{{ $visibility->value }}" @selected(old('visibility', $transcript->visibility?->value ?? 'admin_only') === $visibility->value)>{{ $visibility->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.select name="source_type" label="Source Type">
        @foreach ($sourceTypes as $sourceType)
            <option value="{{ $sourceType->value }}" @selected(old('source_type', $transcript->source_type?->value ?? 'manual') === $sourceType->value)>{{ $sourceType->label() }}</option>
        @endforeach
    </x-ui.select>
    <div class="md:col-span-2">
        <x-ui.textarea name="passage_note" label="Passage Note (admin only)" rows="3">{{ old('passage_note', $transcript->passage_note) }}</x-ui.textarea>
    </div>
    <div class="md:col-span-2">
        <x-ui.textarea name="transcript_text" label="Transcript Text" rows="8" required placeholder="Paste the full listening script here. Example: Man: Good morning. Woman: I would like to book a room.">{{ old('transcript_text', $transcript->transcript_text) }}</x-ui.textarea>
        <p class="mt-1 text-xs aa-muted">Minimum 10 characters. This is admin-only reference text.</p>
    </div>
    <div class="md:col-span-2">
        <x-ui.textarea name="formatted_transcript" label="Formatted Transcript" rows="6">{{ old('formatted_transcript', $transcript->formatted_transcript) }}</x-ui.textarea>
    </div>
    <div class="md:col-span-2">
        @include('admin.listening.transcripts.partials.timestamp-editor')
    </div>
    <div class="flex items-center gap-2">
        <x-ui.checkbox name="is_official" label="Official transcript" :checked="old('is_official', $transcript->is_official)" />
    </div>
</div>

<div class="mt-6 flex justify-end gap-2">
    <x-ui.button href="{{ $cancelUrl ?? route($routePrefix.'.index') }}" variant="outline">Cancel</x-ui.button>
    <x-ui.button type="submit">{{ $submitLabel }}</x-ui.button>
</div>
