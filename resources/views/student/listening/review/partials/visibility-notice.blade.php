@if (!($visibility['can_show_transcript_highlight'] ?? false) && !($visibility['can_show_audio_review'] ?? false))
    <x-ui.card class="mt-6" title="Review notice">
        <p class="text-sm aa-muted">Some review materials are hidden based on your test settings.</p>
    </x-ui.card>
@endif
