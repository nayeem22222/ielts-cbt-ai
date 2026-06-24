<x-ui.card title="Bulk Import" class="mt-6">
    <p class="mb-4 text-sm aa-muted">
        @if ($type->usesRomanOptionKeys())
            Options: one per line as <code class="rounded bg-neutral-100 px-1 dark:bg-neutral-800">i | Heading text</code>.
            Questions: <code class="rounded bg-neutral-100 px-1 dark:bg-neutral-800">14 | Paragraph A | ii</code>
        @else
            Options: one per line as <code class="rounded bg-neutral-100 px-1 dark:bg-neutral-800">A</code> or <code class="rounded bg-neutral-100 px-1 dark:bg-neutral-800">A | Label</code>.
            Questions: <code class="rounded bg-neutral-100 px-1 dark:bg-neutral-800">1 | statement text | H</code>
        @endif
    </p>

    <form method="POST" action="{{ route('admin.reading-question-groups.matching.bulk-import', $group) }}" class="grid gap-4 md:grid-cols-2">
        @csrf
        <x-ui.textarea name="options_text" label="Bulk Options" rows="8" placeholder="A&#10;B&#10;C"></x-ui.textarea>
        <x-ui.textarea name="questions_text" label="Bulk Questions" rows="8" placeholder="1 | statement | A"></x-ui.textarea>
        <div class="md:col-span-2">
            <x-ui.button type="submit">Import</x-ui.button>
        </div>
    </form>
</x-ui.card>
