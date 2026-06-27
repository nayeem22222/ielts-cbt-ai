<x-ui.card title="Section Readiness">
    <dl class="grid gap-4 sm:grid-cols-2">
        <div><dt class="text-xs uppercase aa-muted">Valid Range</dt><dd>{{ $readiness['has_valid_range'] ? 'Yes' : 'No' }}</dd></div>
        <div><dt class="text-xs uppercase aa-muted">Audio</dt><dd>{{ $readiness['has_audio'] ? 'Attached' : 'Missing' }}</dd></div>
        <div><dt class="text-xs uppercase aa-muted">Transcript</dt><dd>{{ $readiness['has_transcript'] ? 'Attached' : 'None' }}</dd></div>
        <div><dt class="text-xs uppercase aa-muted">Timestamped</dt><dd>{{ ($readiness['has_timestamped_transcript'] ?? false) ? 'Yes' : 'No' }}</dd></div>
        <div><dt class="text-xs uppercase aa-muted">Audio Match</dt><dd>{{ ($readiness['transcript_audio_matches'] ?? true) ? 'Yes' : 'No' }}</dd></div>
        <div><dt class="text-xs uppercase aa-muted">Visibility</dt><dd>{{ $readiness['transcript_visibility'] ?? '—' }}</dd></div>
        <div><dt class="text-xs uppercase aa-muted">Questions</dt><dd>{{ $readiness['questions_count'] }}/{{ $readiness['expected_questions'] }}</dd></div>
        <div><dt class="text-xs uppercase aa-muted">Groups</dt><dd>{{ $readiness['groups_count'] }}</dd></div>
        <div><dt class="text-xs uppercase aa-muted">Ready</dt><dd><x-ui.badge :tone="$readiness['is_ready'] ? 'green' : 'amber'">{{ $readiness['is_ready'] ? 'Yes' : 'No' }}</x-ui.badge></dd></div>
    </dl>
    @if (! empty($readiness['missing']))
        <ul class="mt-4 list-disc space-y-1 pl-5 text-sm text-amber-700 dark:text-amber-200">
            @foreach ($readiness['missing'] as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    @endif
</x-ui.card>
