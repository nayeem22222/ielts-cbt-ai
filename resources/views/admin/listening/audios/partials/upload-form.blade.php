<form method="POST" action="{{ route($routePrefix.'.store') }}" enctype="multipart/form-data" class="space-y-4">
    @csrf
    <x-ui.input type="file" name="audio_file" label="Audio File" accept=".mp3,.wav,.m4a,.aac,.ogg,audio/*" required />
    <p class="text-xs aa-muted">
        Allowed formats: mp3, wav, m4a, aac, ogg.
        Max size: {{ config('listening.audio.max_file_size_mb', 100) }} MB.
        Audio will be processed in the background after upload.
    </p>
    <x-ui.input name="title" label="Title (optional)" :value="old('title')" />
    <x-ui.textarea name="description" label="Description (optional)" rows="3">{{ old('description') }}</x-ui.textarea>
    <x-ui.button type="submit">Upload Audio</x-ui.button>
</form>
