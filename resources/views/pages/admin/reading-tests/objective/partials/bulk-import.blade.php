<x-ui.card title="Bulk Import" class="mt-6">
    <p class="mb-4 text-sm aa-muted">
        @if (in_array($type->value, ['true_false_not_given', 'yes_no_not_given'], true))
            Format: <code class="rounded bg-neutral-100 px-1 dark:bg-neutral-800">1|Statement text|TRUE</code>
        @elseif ($type->value === 'multiple_choice_single')
            Format: <code class="rounded bg-neutral-100 px-1 dark:bg-neutral-800">3|Question text|Option A|Option B|Option C|Option D|A</code>
        @else
            Format: <code class="rounded bg-neutral-100 px-1 dark:bg-neutral-800">4|Question text|Opt A|Opt B|Opt C|Opt D|A,C</code>
        @endif
    </p>
    <form method="POST" action="{{ route('admin.reading-question-groups.objective-questions.bulk-import', $group) }}">
        @csrf
        <x-ui.textarea name="import_text" label="Import Lines" rows="8" required></x-ui.textarea>
        <div class="mt-4">
            <x-ui.button type="submit">Import Questions</x-ui.button>
        </div>
    </form>
</x-ui.card>
