@props([
    'group',
    'test',
    'passage',
    'selected' => false,
    'isFirst' => false,
    'isLast' => false,
])

<div
    data-group-item
    data-group-id="{{ $group->id }}"
    @class([
        'rounded-xl border p-3 transition',
        'border-brand-300 bg-brand-50/60 dark:border-brand-700 dark:bg-brand-950/30' => $selected,
        'border-neutral-200 bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-900/50' => ! $selected,
    ])
>
    <div class="flex items-start gap-2">
        <button
            type="button"
            data-group-drag-handle
            class="mt-0.5 cursor-grab rounded p-1 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800"
            title="Drag to reorder"
            aria-label="Drag question group {{ $group->title }}"
        >
            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M7 4a1 1 0 110-2 1 1 0 010 2zm6-1a1 1 0 100-2 1 1 0 000 2zM7 11a1 1 0 110-2 1 1 0 010 2zm6-1a1 1 0 100-2 1 1 0 000 2zM7 18a1 1 0 110-2 1 1 0 010 2zm6-1a1 1 0 100-2 1 1 0 000 2z"/></svg>
        </button>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-1.5">
                <x-ui.badge tone="blue">{{ $group->question_type?->label() }}</x-ui.badge>
                <x-ui.badge :tone="$group->status?->badgeTone() ?? 'amber'">{{ $group->status_label }}</x-ui.badge>
            </div>
            <h5 class="mt-1 truncate text-sm font-semibold text-neutral-900 dark:text-white">{{ $group->title }}</h5>
            <p class="mt-1 text-xs aa-muted">
                Q{{ $group->question_range_label }} · Questions {{ $group->question_count_label }} · Order {{ $group->sort_order }}
            </p>

            <div class="mt-2 flex flex-wrap gap-1">
                <x-ui.button
                    href="{{ route('admin.reading-tests.builder', ['readingTest' => $test, 'passage' => $passage->id, 'question_group' => $group->id]) }}"
                    size="sm"
                    :variant="$selected ? 'primary' : 'outline'"
                >Edit</x-ui.button>

                @if ($group->question_type?->isMatchingBuilderType())
                    <x-ui.button
                        href="{{ route('admin.reading-question-groups.questions.index', $group) }}"
                        size="sm"
                        variant="outline"
                    >Questions</x-ui.button>
                @elseif ($group->question_type?->isObjectiveBuilderType())
                    <x-ui.button
                        href="{{ route('admin.reading-question-groups.objective-questions.index', $group) }}"
                        size="sm"
                        variant="outline"
                    >Questions</x-ui.button>
                @elseif ($group->question_type?->isCompletionBuilderType())
                    <x-ui.button
                        href="{{ route('admin.reading-question-groups.completion-questions.index', $group) }}"
                        size="sm"
                        variant="outline"
                    >Questions</x-ui.button>
                @endif

                <form method="POST" action="{{ route('admin.reading-tests.passages.groups.duplicate', [$test, $passage, $group]) }}">
                    @csrf
                    <x-ui.button type="submit" size="sm" variant="outline">Duplicate</x-ui.button>
                </form>

                <form
                    method="POST"
                    action="{{ route('admin.reading-tests.passages.groups.destroy', [$test, $passage, $group]) }}"
                    onsubmit="return confirm('Delete {{ $group->title }}? This will also remove all linked questions.')"
                >
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" size="sm" variant="danger">Delete</x-ui.button>
                </form>

                @unless ($isFirst)
                    <form method="POST" action="{{ route('admin.reading-tests.passages.groups.move-up', [$test, $passage, $group]) }}">
                        @csrf
                        <x-ui.button type="submit" size="sm" variant="ghost" title="Move up">↑</x-ui.button>
                    </form>
                @endunless

                @unless ($isLast)
                    <form method="POST" action="{{ route('admin.reading-tests.passages.groups.move-down', [$test, $passage, $group]) }}">
                        @csrf
                        <x-ui.button type="submit" size="sm" variant="ghost" title="Move down">↓</x-ui.button>
                    </form>
                @endunless
            </div>
        </div>
    </div>
</div>
