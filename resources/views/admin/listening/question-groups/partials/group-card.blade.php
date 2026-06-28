@php $g = $groupSummary; @endphp
<div class="rounded-2xl border border-neutral-200 p-4 dark:border-neutral-800">
    <div class="mb-3 flex flex-wrap justify-between gap-3">
        <div>
            <h3 class="font-semibold">{{ $g['title'] ?: 'Question Group #'.$g['id'] }}</h3>
            <p class="text-sm aa-muted">{{ $g['question_type_label'] ?? $g['question_type'] }} · Q{{ $g['start'] }}–Q{{ $g['end'] }} · {{ $g['layout_type'] ?? 'default' }}</p>
        </div>
        <x-ui.badge :tone="($g['questions_count'] ?? 0) >= ($g['total_questions'] ?? 0) ? 'green' : 'amber'">{{ $g['questions_count'] }}/{{ $g['total_questions'] }} questions</x-ui.badge>
    </div>
    @if (! empty($g['missing_numbers']))
        <p class="mb-3 text-xs text-amber-700 dark:text-amber-200">Missing question numbers: {{ implode(', ', $g['missing_numbers']) }}</p>
    @endif
    <div class="flex flex-wrap gap-2">
        @if (($g['questions_count'] ?? 0) < ($g['total_questions'] ?? 0))
            @include('admin.listening.questions.partials.bulk-create-form', ['bulkGroup' => $group, 'size' => 'sm', 'variant' => 'primary'])
        @endif
        <x-ui.button href="{{ route($questionsRoutePrefix.'.index', [$listeningTest, $section, $g['id']]) }}" size="sm" variant="{{ ($g['questions_count'] ?? 0) > 0 ? 'primary' : 'outline' }}">Manage Questions</x-ui.button>
        <x-ui.button href="{{ route($groupsRoutePrefix.'.edit', [$listeningTest, $section, $g['id']]) }}" size="sm" variant="outline">Edit</x-ui.button>
        @if (! empty($group))
            @can('create', [\App\Models\Listening\ListeningQuestionGroup::class, $listeningTest, $section])
                <form method="POST" action="{{ route($groupsRoutePrefix.'.duplicate', [$listeningTest, $section, $group]) }}">
                    @csrf
                    <x-ui.button type="submit" size="sm" variant="outline">Duplicate</x-ui.button>
                </form>
            @endcan
            @can('delete', $group)
                <form method="POST" action="{{ route($groupsRoutePrefix.'.destroy', [$listeningTest, $section, $group]) }}" onsubmit="return confirm('Delete this question group and its questions?')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" size="sm" variant="danger">Delete</x-ui.button>
                </form>
            @endcan
        @endif
    </div>
</div>
