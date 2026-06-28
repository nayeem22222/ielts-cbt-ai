@php
    $loc = old('transcript_location', $question->transcript_location ? json_encode($question->transcript_location, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '');
    if (is_array($loc)) { $loc = json_encode($loc, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); }
@endphp
<div class="md:col-span-2 grid gap-4 sm:grid-cols-2">
    <x-ui.input name="audio_timestamp_start" type="number" step="0.01" min="0" label="Audio Timestamp Start (sec)" :value="old('audio_timestamp_start', $question->audio_timestamp_start)" />
    <x-ui.input name="audio_timestamp_end" type="number" step="0.01" min="0" label="Audio Timestamp End (sec)" :value="old('audio_timestamp_end', $question->audio_timestamp_end)" />
    <div class="sm:col-span-2">
        <x-ui.textarea name="transcript_location" label="Transcript Location (JSON)" rows="4" class="font-mono text-sm">{{ $loc }}</x-ui.textarea>
    </div>
</div>
