@php
    $range = $sectionRangeMap[$section->section_number] ?? null;
@endphp
<div class="rounded-2xl border border-neutral-200 p-4 dark:border-neutral-800">
    <div class="mb-3 flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs uppercase aa-muted">Section {{ $section->section_number }}</p>
            <h3 class="text-lg font-semibold text-neutral-900 dark:text-white">{{ $section->title }}</h3>
            <p class="text-sm aa-muted">{{ $section->section_type?->label() }} · Q{{ $section->start_question_number }}–{{ $section->end_question_number }}</p>
        </div>
        <x-ui.badge :tone="$section->is_active ? 'green' : 'neutral'">{{ $section->is_active ? 'Active' : 'Inactive' }}</x-ui.badge>
    </div>

    <dl class="mb-4 grid gap-2 text-sm sm:grid-cols-2">
        <div><dt class="aa-muted">Audio</dt><dd>{{ $section->audio_id ? ($section->audio?->original_name ?? 'Attached') : 'Missing' }}</dd></div>
        <div><dt class="aa-muted">Transcript</dt><dd>{{ $section->transcript_id ? ($section->transcript?->title ?? 'Attached') : 'None' }}</dd></div>
        <div><dt class="aa-muted">Groups</dt><dd>{{ $section->question_groups_count ?? 0 }} (placeholder)</dd></div>
        <div><dt class="aa-muted">Questions</dt><dd>{{ $section->questions_count ?? 0 }}/{{ $section->total_questions }}</dd></div>
    </dl>

    <div class="flex flex-wrap gap-2">
        @can('view', $section)
            <x-ui.button href="{{ route($sectionsRoutePrefix.'.show', [$listeningTest, $section]) }}" size="sm" variant="outline">View</x-ui.button>
        @endcan
        @can('update', $section)
            <x-ui.button href="{{ route($sectionsRoutePrefix.'.edit', [$listeningTest, $section]) }}" size="sm" variant="outline">Edit</x-ui.button>
        @endcan
        <x-ui.button size="sm" variant="outline" disabled>Manage Questions</x-ui.button>
        <x-ui.button size="sm" variant="outline" disabled>Manage Audio</x-ui.button>
        @can('delete', $section)
            <form method="POST" action="{{ route($sectionsRoutePrefix.'.destroy', [$listeningTest, $section]) }}" onsubmit="return confirm('Delete this section?')">
                @csrf @method('DELETE')
                <x-ui.button type="submit" size="sm" variant="danger">Delete</x-ui.button>
            </form>
        @endcan
    </div>
</div>
