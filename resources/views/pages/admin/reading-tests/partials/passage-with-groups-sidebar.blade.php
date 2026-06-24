@props([
    'passage',
    'test',
    'selectedPassage' => false,
    'requestedGroupId' => 0,
    'isFirst' => false,
    'isLast' => false,
])

<div
    data-passage-item
    data-passage-id="{{ $passage->id }}"
    @class([
        'rounded-2xl border transition',
        'border-brand-300 bg-brand-50/40 dark:border-brand-700 dark:bg-brand-950/20' => $selectedPassage && ! $requestedGroupId,
        'border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900' => ! $selectedPassage || $requestedGroupId,
    ])
>
    <div class="flex items-start gap-3 p-4">
        <button
            type="button"
            data-passage-drag-handle
            class="mt-1 cursor-grab rounded-lg p-1 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800"
            title="Drag to reorder passages"
            aria-label="Drag passage {{ $passage->part_number }}"
        >
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M7 4a1 1 0 110-2 1 1 0 010 2zm6-1a1 1 0 100-2 1 1 0 000 2zM7 11a1 1 0 110-2 1 1 0 010 2zm6-1a1 1 0 100-2 1 1 0 000 2zM7 18a1 1 0 110-2 1 1 0 010 2zm6-1a1 1 0 100-2 1 1 0 000 2z"/></svg>
        </button>

        <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-2">
                <button
                    type="button"
                    class="flex items-center gap-2 text-left"
                    @click="togglePassage({{ $passage->id }})"
                    title="Show or hide question groups"
                >
                    <svg
                        class="h-4 w-4 shrink-0 text-neutral-500 transition"
                        :class="isPassageExpanded({{ $passage->id }}) ? 'rotate-90' : ''"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    ><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                    <span class="text-xs font-semibold uppercase tracking-wide text-brand-600 dark:text-brand-300">Passage {{ $passage->part_number }}</span>
                </button>
                <x-ui.badge :tone="$passage->status?->badgeTone() ?? 'amber'">{{ $passage->status_label }}</x-ui.badge>
            </div>

            <h4 class="mt-1 truncate font-semibold text-neutral-900 dark:text-white">{{ $passage->title }}</h4>
            <p class="mt-1 text-xs aa-muted">Q{{ $passage->question_range_label }} · {{ $passage->groups->count() }} {{ Str::plural('group', $passage->groups->count()) }}</p>

            <div class="mt-2 flex flex-wrap gap-1">
                <x-ui.button
                    href="{{ route('admin.reading-tests.builder', ['readingTest' => $test, 'passage' => $passage->id]) }}"
                    size="sm"
                    :variant="$selectedPassage && ! $requestedGroupId ? 'primary' : 'outline'"
                >Edit Passage</x-ui.button>

                <form method="POST" action="{{ route('admin.reading-tests.passages.groups.store', [$test, $passage]) }}">
                    @csrf
                    <x-ui.button type="submit" size="sm" variant="outline">Add Group</x-ui.button>
                </form>

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

    <div
        class="border-t border-neutral-200 px-4 pb-4 pt-3 dark:border-neutral-800"
        x-show="isPassageExpanded({{ $passage->id }})"
    >
        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide aa-muted">Question Groups</p>

        <form id="group-reorder-form-{{ $passage->id }}" method="POST" action="{{ route('admin.reading-tests.passages.groups.reorder', [$test, $passage]) }}">
            @csrf
            <div data-group-ids>
                @foreach ($passage->groups as $group)
                    <input type="hidden" name="group_ids[]" value="{{ $group->id }}">
                @endforeach
            </div>
        </form>

        <div data-group-sortable-list="{{ $passage->id }}" class="space-y-2">
            @forelse ($passage->groups as $group)
                @include('pages.admin.reading-tests.partials.group-sidebar-item', [
                    'group' => $group,
                    'test' => $test,
                    'passage' => $passage,
                    'selected' => (int) $requestedGroupId === (int) $group->id,
                    'isFirst' => $loop->first,
                    'isLast' => $loop->last,
                ])
            @empty
                <p class="rounded-xl border border-dashed border-neutral-200 px-3 py-4 text-center text-xs aa-muted dark:border-neutral-700">No question groups yet. Click <strong>Add Group</strong> above.</p>
            @endforelse
        </div>

        <form method="POST" action="{{ route('admin.reading-tests.passages.groups.store', [$test, $passage]) }}" class="mt-3">
            @csrf
            <x-ui.button type="submit" size="sm" class="w-full" variant="outline">Add Question Group</x-ui.button>
        </form>
    </div>
</div>
