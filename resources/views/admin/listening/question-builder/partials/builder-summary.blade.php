<x-ui.card title="Test Question Builder Summary">
    <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 text-sm">
        <div><dt class="aa-muted">Questions</dt><dd class="text-lg font-semibold">{{ $summary['questions_count'] }}/{{ $summary['expected_questions'] }}</dd></div>
        <div><dt class="aa-muted">Groups</dt><dd class="text-lg font-semibold">{{ $summary['groups_count'] }}</dd></div>
        <div><dt class="aa-muted">Sections</dt><dd class="text-lg font-semibold">{{ $summary['sections_count'] }}/4</dd></div>
        <div><dt class="aa-muted">Completion</dt><dd class="text-lg font-semibold">{{ $summary['completion_percentage'] }}%</dd></div>
    </dl>

    @if (! empty($summary['missing_numbers']))
        <p class="mt-4 text-sm text-amber-700 dark:text-amber-200">Missing question numbers: {{ implode(', ', $summary['missing_numbers']) }}</p>
    @endif
    @if (! empty($summary['duplicate_numbers']))
        <p class="mt-2 text-sm text-red-700 dark:text-red-300">Duplicate numbers: {{ implode(', ', $summary['duplicate_numbers']) }}</p>
    @endif
    @if (! empty($summary['invalid_numbers']))
        <p class="mt-2 text-sm text-red-700 dark:text-red-300">Invalid numbers: {{ implode(', ', $summary['invalid_numbers']) }}</p>
    @endif

    <div class="mt-4">
        <x-ui.badge :tone="$summary['is_complete'] ? 'green' : 'amber'">{{ $summary['is_complete'] ? 'Builder Complete' : 'Incomplete' }}</x-ui.badge>
    </div>
</x-ui.card>
