@props(['group', 'type'])

<x-ui.card class="mb-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-600">Completion Question Builder</p>
            <h2 class="mt-1 text-xl font-bold text-neutral-900 dark:text-white">{{ $group->title }}</h2>
            <p class="mt-1 text-sm aa-muted">{{ $type->label() }} · Questions {{ $group->question_range_label }} · Filled {{ $group->question_count_label }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-ui.badge tone="blue">{{ $type->label() }}</x-ui.badge>
            <x-ui.badge :tone="$group->status?->badgeTone() ?? 'amber'">{{ $group->status_label }}</x-ui.badge>
        </div>
    </div>

    @if ($group->instruction)
        <div class="mt-4 rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm dark:border-neutral-800 dark:bg-neutral-900">
            <p class="font-medium text-neutral-800 dark:text-neutral-100">Instruction</p>
            <p class="mt-1 aa-muted">{{ $group->instruction }}</p>
        </div>
    @endif
</x-ui.card>
