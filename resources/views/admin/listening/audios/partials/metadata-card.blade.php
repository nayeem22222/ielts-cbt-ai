<x-ui.card title="Metadata">
    <dl class="grid gap-3 sm:grid-cols-2 text-sm">
        <div><dt class="aa-muted">Duration</dt><dd>{{ $audio->duration_seconds ? $audio->duration_seconds.' seconds' : '—' }}</dd></div>
        <div><dt class="aa-muted">Bitrate</dt><dd>{{ $audio->bitrate ? number_format($audio->bitrate).' bps' : '—' }}</dd></div>
        <div><dt class="aa-muted">Sample Rate</dt><dd>{{ $audio->sample_rate ? number_format($audio->sample_rate).' Hz' : '—' }}</dd></div>
        <div><dt class="aa-muted">Channels</dt><dd>{{ $audio->channels ?? '—' }}</dd></div>
        <div><dt class="aa-muted">Format</dt><dd>{{ strtoupper((string) ($audio->format ?: $audio->extension)) }}</dd></div>
        <div><dt class="aa-muted">File Size</dt><dd>{{ number_format(($audio->file_size ?? 0) / 1024 / 1024, 2) }} MB</dd></div>
        <div class="sm:col-span-2"><dt class="aa-muted">Checksum</dt><dd class="break-all font-mono text-xs">{{ $audio->checksum ?? '—' }}</dd></div>
        <div><dt class="aa-muted">Loudness</dt><dd>{{ $audio->loudness_lufs !== null ? $audio->loudness_lufs.' LUFS' : '—' }}</dd></div>
        <div><dt class="aa-muted">Peak</dt><dd>{{ $audio->peak_db !== null ? $audio->peak_db.' dB' : '—' }}</dd></div>
    </dl>
</x-ui.card>
