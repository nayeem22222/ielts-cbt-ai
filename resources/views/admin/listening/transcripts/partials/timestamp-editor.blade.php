@php
    $timestampJson = old(
        'timestamped_transcript',
        $transcript->timestamped_transcript
            ? json_encode($transcript->timestamped_transcript, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            : ''
    );
    if (is_array($timestampJson)) {
        $timestampJson = json_encode($timestampJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
@endphp
<x-ui.textarea name="timestamped_transcript" label="Timestamped Transcript (JSON)" rows="10" class="font-mono text-sm">{{ $timestampJson }}</x-ui.textarea>
<p class="mt-1 text-xs aa-muted">Each line: line, speaker, start, end, text. Timestamps must be sequential without overlap.</p>
