<x-ui.card title="Audio Timestamp Debug">
    <dl class="grid gap-2 text-sm">
        <div>Start: {{ $item['audio_timestamp_start'] ?? '—' }}s</div>
        <div>End: {{ $item['audio_timestamp_end'] ?? '—' }}s</div>
        <div>Section: {{ $item['section_number'] ?? '—' }}</div>
    </dl>
</x-ui.card>
