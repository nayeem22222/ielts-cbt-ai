<x-ui.card title="Admin Passage Preview">
    <x-ui.alert tone="amber" class="mb-4">{{ $preview['admin_warning'] }}</x-ui.alert>

    @if ($preview['note'])
        <p class="mb-4 text-sm"><span class="aa-muted">Passage note:</span> {{ $preview['note'] }}</p>
    @endif

    <div class="prose prose-sm max-w-none dark:prose-invert">
        <pre class="whitespace-pre-wrap text-sm">{{ $preview['plain_text'] }}</pre>
    </div>

    @if (! empty($preview['timestamp_blocks']))
        <div class="mt-4">
            <h4 class="mb-2 text-sm font-semibold">Timestamp blocks</h4>
            <div class="space-y-2 text-sm">
                @foreach ($preview['timestamp_blocks'] as $block)
                    <div class="rounded border border-neutral-200 p-2 dark:border-neutral-700">
                        <span class="aa-muted">[{{ number_format($block['start'], 2) }}–{{ $block['end'] !== null ? number_format($block['end'], 2) : '?' }}]</span>
                        @if (! empty($block['speaker']))
                            <strong>{{ $block['speaker'] }}:</strong>
                        @endif
                        {{ $block['text'] }}
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($preview['question_builder_note'])
        <p class="mt-4 text-xs aa-muted">{{ $preview['question_builder_note'] }}</p>
    @endif
</x-ui.card>
