<x-ui.card title="Admin Preview" subtitle="Instruction, template, blanks, and answer rule">
    @php
        $rule = collect($answerRules)->first(fn ($item) => $item->value === ($settings['answer_rule'] ?? ''));
    @endphp

    @if ($group->instruction)
        <div class="mb-4 rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm dark:border-neutral-800 dark:bg-neutral-900">
            <p class="font-medium">Instruction</p>
            <p class="mt-1 aa-muted">{{ $group->instruction }}</p>
        </div>
    @endif

    <div class="mb-4 rounded-2xl border border-neutral-200 px-4 py-3 text-sm dark:border-neutral-800">
        <p class="font-medium">Answer Rule</p>
        <p class="mt-1 aa-muted">{{ $rule?->label() ?? strtoupper(str_replace('_', ' ', $settings['answer_rule'] ?? '')) }}</p>
        @if (!empty($settings['custom_answer_rule']))
            <p class="mt-1 text-xs aa-muted">{{ $settings['custom_answer_rule'] }}</p>
        @endif
    </div>

    <div class="mb-6 rounded-3xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-950">
        <div class="prose max-w-none dark:prose-invert">
            {!! $previewHtml !!}
        </div>
    </div>

    <div class="space-y-3">
        <h3 class="text-sm font-semibold">Answer Key</h3>
        @forelse ($questions as $question)
            @php
                $correct = $question->correctAnswers->first();
                $alternatives = \App\Support\Reading\CompletionAnswerPayload::alternatives($correct);
            @endphp
            <div class="rounded-xl border border-neutral-200 px-4 py-3 text-sm dark:border-neutral-700">
                <span class="font-bold text-brand-700">Q{{ $question->question_number }}</span>
                <span class="mx-2">→</span>
                <span>{{ $correct?->answer ?: '—' }}</span>
                @if ($alternatives !== [])
                    <span class="aa-muted"> (also: {{ implode(', ', $alternatives) }})</span>
                @endif
            </div>
        @empty
            <p class="text-sm aa-muted">No questions configured.</p>
        @endforelse
    </div>
</x-ui.card>
