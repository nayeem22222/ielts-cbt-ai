@props(['summary'])

<x-ui.card title="Builder Summary" class="mb-4">
    <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 text-sm">
        <div>
            <dt class="aa-muted">Sections</dt>
            <dd class="text-lg font-semibold">{{ $summary['sections_count'] ?? 0 }}</dd>
        </div>
        <div>
            <dt class="aa-muted">Question Groups</dt>
            <dd class="text-lg font-semibold">{{ $summary['groups_count'] ?? 0 }}</dd>
        </div>
        <div>
            <dt class="aa-muted">Questions</dt>
            <dd class="text-lg font-semibold">{{ $summary['questions_count'] ?? 0 }}/{{ $summary['expected_questions'] ?? 40 }}</dd>
        </div>
        <div>
            <dt class="aa-muted">Completion</dt>
            <dd class="text-lg font-semibold">{{ $summary['completion_percentage'] ?? 0 }}%</dd>
        </div>
    </dl>

    @if (! empty($summary['missing_numbers']))
        <p class="mt-3 text-sm text-amber-700 dark:text-amber-200">
            Missing question numbers: {{ implode(', ', $summary['missing_numbers']) }}
        </p>
    @endif

    @if (! empty($summary['duplicate_numbers']))
        <p class="mt-2 text-sm text-red-600 dark:text-red-300">
            Duplicate question numbers: {{ implode(', ', $summary['duplicate_numbers']) }}
        </p>
    @endif
</x-ui.card>
