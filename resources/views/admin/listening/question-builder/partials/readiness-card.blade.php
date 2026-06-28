<x-ui.card title="Section Question Readiness">
    <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 text-sm">
        <div><dt class="aa-muted">Groups</dt><dd>{{ $summary['groups_count'] }}</dd></div>
        <div><dt class="aa-muted">Questions</dt><dd>{{ $summary['questions_count'] }}/{{ $summary['expected_questions'] }}</dd></div>
        <div><dt class="aa-muted">Audio</dt><dd>{{ $summary['has_audio'] ? 'Yes' : 'No' }}</dd></div>
        <div><dt class="aa-muted">Transcript</dt><dd>{{ $summary['has_transcript'] ? 'Yes' : 'No' }}</dd></div>
    </dl>
    @if (! empty($summary['missing_numbers']))
        <p class="mt-3 text-sm text-amber-700 dark:text-amber-200">Missing question numbers: {{ implode(', ', $summary['missing_numbers']) }}</p>
    @endif
    <div class="mt-3"><x-ui.badge :tone="$summary['is_complete'] ? 'green' : 'amber'">{{ $summary['is_complete'] ? 'Section Complete' : 'Incomplete' }}</x-ui.badge></div>
</x-ui.card>
