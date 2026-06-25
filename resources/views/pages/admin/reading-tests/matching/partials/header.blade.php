<x-ui.card title="Question Group" padding="p-5" class="mb-6">
    <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 text-sm">
        <div>
            <dt class="text-xs uppercase aa-muted">Reading Test</dt>
            <dd class="font-semibold text-neutral-900 dark:text-white">{{ $test->title }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase aa-muted">Passage</dt>
            <dd class="font-semibold">{{ $passage->title }} (Part {{ $passage->part_number }})</dd>
        </div>
        <div>
            <dt class="text-xs uppercase aa-muted">Group</dt>
            <dd class="font-semibold">{{ $group->title }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase aa-muted">Question Type</dt>
            <dd><x-ui.badge tone="blue">{{ $group->question_type?->label() }}</x-ui.badge></dd>
        </div>
        <div>
            <dt class="text-xs uppercase aa-muted">Question Range</dt>
            <dd class="font-semibold">Q{{ $group->question_range_label }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase aa-muted">Progress</dt>
            <dd class="font-semibold">{{ $group->question_count_label }} created</dd>
        </div>
    </dl>
</x-ui.card>

@include('pages.admin.reading-tests.partials.interaction-settings', ['group' => $group, 'mode' => 'matching'])
