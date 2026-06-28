@props([
    'sectionItem',
    'listeningTest',
    'selectedSection' => false,
    'requestedGroupId' => 0,
    'isFirst' => false,
    'isLast' => false,
])

<div
    data-section-item
    data-section-id="{{ $sectionItem->id }}"
    @class([
        'rounded-2xl border transition',
        'border-brand-300 bg-brand-50/40 dark:border-brand-700 dark:bg-brand-950/20' => $selectedSection && ! $requestedGroupId,
        'border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900' => ! $selectedSection || $requestedGroupId,
    ])
>
    <div class="flex items-start gap-3 p-4">
        <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-2">
                <button
                    type="button"
                    class="flex items-center gap-2 text-left"
                    @click="toggleSection({{ $sectionItem->id }})"
                    title="Show or hide question groups"
                >
                    <svg
                        class="h-4 w-4 shrink-0 text-neutral-500 transition"
                        :class="isSectionExpanded({{ $sectionItem->id }}) ? 'rotate-90' : ''"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    ><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                    <span class="text-xs font-semibold uppercase tracking-wide text-brand-600 dark:text-brand-300">Section {{ $sectionItem->section_number }}</span>
                </button>
                <x-ui.badge :tone="$sectionItem->is_active ? 'green' : 'neutral'">{{ $sectionItem->is_active ? 'Active' : 'Inactive' }}</x-ui.badge>
            </div>

            <h4 class="mt-1 truncate font-semibold text-neutral-900 dark:text-white">{{ $sectionItem->title }}</h4>
            <p class="mt-1 text-xs aa-muted">
                Q{{ $sectionItem->start_question_number }}–{{ $sectionItem->end_question_number }}
                · {{ $sectionItem->question_groups_count ?? $sectionItem->questionGroups->count() }} {{ Str::plural('group', $sectionItem->questionGroups->count()) }}
                · {{ $sectionItem->questions_count ?? 0 }}/{{ $sectionItem->total_questions }} questions
            </p>

            <div class="mt-2 flex flex-wrap gap-1">
                <x-ui.button
                    href="{{ route('admin.listening.tests.builder.index', ['listeningTest' => $listeningTest, 'section' => $sectionItem->id]) }}"
                    size="sm"
                    :variant="$selectedSection && ! $requestedGroupId ? 'primary' : 'outline'"
                >View Section</x-ui.button>

                @can('create', [\App\Models\Listening\ListeningQuestionGroup::class, $listeningTest, $sectionItem])
                    <form method="POST" action="{{ route('admin.listening.tests.sections.groups.store-blank', [$listeningTest, $sectionItem]) }}">
                        @csrf
                        <x-ui.button type="submit" size="sm" variant="outline">Add Group</x-ui.button>
                    </form>
                @endcan

                <x-ui.button href="{{ route('admin.listening.tests.sections.show', [$listeningTest, $sectionItem]) }}" size="sm" variant="outline">Audio</x-ui.button>
            </div>
        </div>
    </div>

    <div
        class="border-t border-neutral-200 px-4 pb-4 pt-3 dark:border-neutral-800"
        x-show="isSectionExpanded({{ $sectionItem->id }})"
    >
        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide aa-muted">Question Groups</p>

        <form id="group-reorder-form-{{ $sectionItem->id }}" method="POST" action="{{ route('admin.listening.tests.sections.groups.reorder', [$listeningTest, $sectionItem]) }}">
            @csrf
            <div data-group-ids>
                @foreach ($sectionItem->questionGroups as $group)
                    <input type="hidden" name="group_ids[]" value="{{ $group->id }}">
                @endforeach
            </div>
        </form>

        <div data-group-sortable-list="{{ $sectionItem->id }}" class="space-y-2">
            @forelse ($sectionItem->questionGroups as $group)
                @include('admin.listening.question-builder.partials.group-sidebar-item', [
                    'group' => $group,
                    'listeningTest' => $listeningTest,
                    'section' => $sectionItem,
                    'selected' => (int) $requestedGroupId === (int) $group->id,
                    'isFirst' => $loop->first,
                    'isLast' => $loop->last,
                ])
            @empty
                <p class="rounded-xl border border-dashed border-neutral-200 px-3 py-4 text-center text-xs aa-muted dark:border-neutral-700">
                    No question groups yet. Add your first group.
                </p>
            @endforelse
        </div>

        @can('create', [\App\Models\Listening\ListeningQuestionGroup::class, $listeningTest, $sectionItem])
            <form method="POST" action="{{ route('admin.listening.tests.sections.groups.store-blank', [$listeningTest, $sectionItem]) }}" class="mt-3">
                @csrf
                <x-ui.button type="submit" size="sm" class="w-full" variant="outline">Add Question Group</x-ui.button>
            </form>
        @endcan
    </div>
</div>
