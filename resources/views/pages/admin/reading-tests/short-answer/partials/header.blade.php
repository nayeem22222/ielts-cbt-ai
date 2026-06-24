@props(['test', 'passage', 'group', 'type'])

<x-ui.card class="mb-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-600">Short Answer Builder</p>
            <h2 class="mt-1 text-xl font-bold text-neutral-900 dark:text-white">{{ $group->title }}</h2>
            <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="aa-muted">Reading Test</dt>
                    <dd class="font-medium">{{ $test->title }}</dd>
                </div>
                <div>
                    <dt class="aa-muted">Passage</dt>
                    <dd class="font-medium">{{ $passage->title }} (Part {{ $passage->part_number }})</dd>
                </div>
                <div>
                    <dt class="aa-muted">Question Type</dt>
                    <dd class="font-medium">{{ $type->label() }}</dd>
                </div>
                <div>
                    <dt class="aa-muted">Question Range</dt>
                    <dd class="font-medium">Q{{ $group->question_range_label }}</dd>
                </div>
                <div>
                    <dt class="aa-muted">Questions Created</dt>
                    <dd class="font-medium">{{ $group->question_count_label }}</dd>
                </div>
            </dl>
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
