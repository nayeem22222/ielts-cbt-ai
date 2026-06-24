@props([
    'passage',
    'test',
    'selected' => false,
    'isFirst' => false,
    'isLast' => false,
])

<div
    data-passage-item
    data-passage-id="{{ $passage->id }}"
    @class([
        'rounded-2xl border p-4 transition',
        'border-brand-300 bg-brand-50/60 dark:border-brand-700 dark:bg-brand-950/30' => $selected,
        'border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900' => ! $selected,
    ])
>
    <div class="flex items-start gap-3">
        <button
            type="button"
            data-passage-drag-handle
            class="mt-1 cursor-grab rounded-lg p-1 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800"
            title="Drag to reorder"
            aria-label="Drag passage {{ $passage->part_number }}"
        >
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M7 4a1 1 0 110-2 1 1 0 010 2zm6-1a1 1 0 100-2 1 1 0 000 2zM7 11a1 1 0 110-2 1 1 0 010 2zm6-1a1 1 0 100-2 1 1 0 000 2zM7 18a1 1 0 110-2 1 1 0 010 2zm6-1a1 1 0 100-2 1 1 0 000 2z"/></svg>
        </button>

        <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-brand-600 dark:text-brand-300">Passage {{ $passage->part_number }}</p>
                <x-ui.badge :tone="$passage->status?->badgeTone() ?? 'amber'">{{ $passage->status_label }}</x-ui.badge>
            </div>
            <h4 class="mt-1 truncate font-semibold text-neutral-900 dark:text-white">{{ $passage->title }}</h4>
            @if ($passage->subtitle)
                <p class="truncate text-xs aa-muted">{{ $passage->subtitle }}</p>
            @endif
            <p class="mt-2 text-xs aa-muted">
                Q{{ $passage->question_range_label }} · {{ $passage->questions_count }} {{ Str::plural('question', $passage->questions_count) }}
            </p>

            <div class="mt-3 flex flex-wrap gap-1">
                <x-ui.button href="{{ route('admin.reading-tests.builder', ['readingTest' => $test, 'passage' => $passage->id]) }}" size="sm" :variant="$selected ? 'primary' : 'outline'">Edit</x-ui.button>

                <form method="POST" action="{{ route('admin.reading-tests.passages.duplicate', [$test, $passage]) }}">
                    @csrf
                    <x-ui.button type="submit" size="sm" variant="outline">Duplicate</x-ui.button>
                </form>

                @unless ($isFirst)
                    <form method="POST" action="{{ route('admin.reading-tests.passages.move-up', [$test, $passage]) }}">
                        @csrf
                        <x-ui.button type="submit" size="sm" variant="ghost" title="Move up">↑</x-ui.button>
                    </form>
                @endunless

                @unless ($isLast)
                    <form method="POST" action="{{ route('admin.reading-tests.passages.move-down', [$test, $passage]) }}">
                        @csrf
                        <x-ui.button type="submit" size="sm" variant="ghost" title="Move down">↓</x-ui.button>
                    </form>
                @endunless
            </div>
        </div>
    </div>
</div>
