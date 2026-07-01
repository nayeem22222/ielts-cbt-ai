<x-ui.card title="Transcript Debug">
    <dl class="grid gap-2 text-sm">
        <div>Lines: {{ $item['transcript_line_start'] ?? '—' }} – {{ $item['transcript_line_end'] ?? '—' }}</div>
        <div>Snippet: {{ $item['transcript_text_snippet'] ?? '—' }}</div>
    </dl>
    <pre class="mt-3 overflow-x-auto text-xs">{{ json_encode($item['highlighted_transcript'] ?? null, JSON_PRETTY_PRINT) }}</pre>
    <pre class="mt-3 overflow-x-auto text-xs">{{ json_encode($item['admin_meta']['transcript_reference'] ?? null, JSON_PRETTY_PRINT) }}</pre>
</x-ui.card>
