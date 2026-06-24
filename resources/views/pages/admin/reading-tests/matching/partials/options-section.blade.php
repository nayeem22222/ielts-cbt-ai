<x-ui.card title="Options" :subtitle="$optionTextLabel">
    <form method="POST" action="{{ route('admin.reading-question-groups.matching.options.store', $group) }}" class="mb-5 grid gap-3 md:grid-cols-4">
        @csrf
        <x-ui.input name="option_key" label="{{ $optionKeyLabel }}" placeholder="{{ $group->question_type->usesRomanOptionKeys() ? 'i' : 'A' }}" required />
        <x-ui.input name="option_label" label="{{ $optionTextLabel }}" class="md:col-span-2" />
        <div class="flex items-end">
            <x-ui.button type="submit" class="w-full">Add Option</x-ui.button>
        </div>
    </form>

    <form id="matching-option-reorder-form" method="POST" action="{{ route('admin.reading-question-groups.matching.reorder', $group) }}">
        @csrf
        <div data-option-ids>
            @foreach ($options as $option)
                <input type="hidden" name="option_ids[]" value="{{ $option->id }}">
            @endforeach
        </div>
    </form>

    <div id="matching-option-sortable" class="space-y-2">
        @forelse ($options as $option)
            <div data-option-item data-option-id="{{ $option->id }}" class="rounded-xl border border-neutral-200 bg-neutral-50 p-3 dark:border-neutral-700 dark:bg-neutral-900/50">
                <form method="POST" action="{{ route('admin.reading-question-options.update', $option) }}" class="grid gap-2 md:grid-cols-12 md:items-end">
                    @csrf
                    @method('PUT')
                    <div class="md:col-span-2">
                        <label class="text-xs aa-muted">Key</label>
                        <input name="option_key" value="{{ $option->option_key }}" required class="mt-1 w-full rounded-lg border border-neutral-300 bg-white px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                    </div>
                    <div class="md:col-span-7">
                        <label class="text-xs aa-muted">Label</label>
                        <input name="option_label" value="{{ $option->option_label }}" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                    </div>
                    <div class="flex gap-1 md:col-span-3">
                        <x-ui.button type="submit" size="sm">Save</x-ui.button>
                        <x-ui.button type="button" size="sm" variant="danger" data-option-drag-handle title="Drag to reorder">↕</x-ui.button>
                    </div>
                </form>
                <form method="POST" action="{{ route('admin.reading-question-options.destroy', $option) }}" class="mt-2 flex justify-end" onsubmit="return confirm('Delete option {{ $option->option_key }}? Questions using it will need reassignment.')">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="confirm_delete" value="1">
                    <x-ui.button type="submit" size="sm" variant="ghost">Delete</x-ui.button>
                </form>
            </div>
        @empty
            <x-ui.empty-state title="No options yet">Add options for this matching group (e.g. A–H or i–x).</x-ui.empty-state>
        @endforelse
    </div>
</x-ui.card>
