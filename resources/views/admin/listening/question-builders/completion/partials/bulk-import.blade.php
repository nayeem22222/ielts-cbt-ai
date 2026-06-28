<x-ui.card title="Bulk Import" class="mt-6">
    <form method="POST" action="{{ route('admin.listening-question-groups.completion-questions.bulk-import', $group) }}" class="space-y-4">
        @csrf

        @if (session('completion_confirm_remove'))
            <input type="hidden" name="confirm_remove" value="1">
        @endif

        @if ($type->value === 'sentence_completion')
            <p class="text-sm aa-muted">Format: <code>31 | The first bridge was built in _________. | 1924 | 1924, nineteen twenty-four</code></p>
        @elseif ($type->value === 'table_completion')
            <p class="text-sm aa-muted">Paste a markdown table. Use <code>@{{36}}</code> or <code>[Blank:36]</code> in cells.</p>
        @elseif ($type->value === 'flowchart_completion')
            <p class="text-sm aa-muted">One step per line. Use <code>↓</code> between steps. Blanks: <code>@{{38}}</code></p>
        @else
            <p class="text-sm aa-muted">Paste summary or notes with placeholders like <code>@{{27}}</code> or <code>[Blank:27]</code>.</p>
        @endif

        <x-ui.textarea name="import_text" rows="8" placeholder="Paste content here…"></x-ui.textarea>
        <x-ui.button type="submit">Import &amp; Detect Blanks</x-ui.button>
    </form>
</x-ui.card>
