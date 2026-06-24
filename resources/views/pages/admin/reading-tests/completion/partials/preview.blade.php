<x-ui.card title="Admin Preview" subtitle="Template with highlighted blanks">
    <div class="mb-6 rounded-3xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-950">
        <div class="prose max-w-none dark:prose-invert">
            {!! $previewHtml !!}
        </div>
    </div>

    <div class="space-y-3">
        <h3 class="text-sm font-semibold">Answer Key</h3>
        @forelse ($questions as $question)
            @php $correct = $question->correctAnswers->first(); @endphp
            <div class="rounded-xl border border-neutral-200 px-4 py-3 text-sm dark:border-neutral-700">
                <span class="font-bold text-brand-700">Q{{ $question->question_number }}</span>
                <span class="mx-2">→</span>
                <span>{{ $correct?->answer ?: '—' }}</span>
                @if ($correct?->answer_json && count($correct->answer_json) > 1)
                    <span class="aa-muted"> (also: {{ collect($correct->answer_json)->reject(fn ($v) => strcasecmp($v, (string) $correct->answer) === 0)->implode(', ') }})</span>
                @endif
            </div>
        @empty
            <p class="text-sm aa-muted">No questions configured.</p>
        @endforelse
    </div>
</x-ui.card>
