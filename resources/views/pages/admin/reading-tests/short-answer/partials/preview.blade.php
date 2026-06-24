@php
    $rule = collect($answerRules)->first(fn ($item) => $item->value === ($settings['answer_rule'] ?? ''));
@endphp

<x-ui.card title="Admin Preview" subtitle="Short answer questions with input placeholders">
    <div class="space-y-4">
        @if ($group->instruction)
            <p class="text-sm italic aa-muted">{{ $group->instruction }}</p>
        @endif

        <div>
            <p class="text-xs font-semibold uppercase aa-muted">Answer Rule</p>
            <p class="text-sm font-medium">
                {{ $rule?->label() ?? strtoupper(str_replace('_', ' ', $settings['answer_rule'] ?? '')) }}
                @if (($settings['answer_rule'] ?? '') === 'custom' && ! empty($settings['custom_answer_rule']))
                    — {{ $settings['custom_answer_rule'] }}
                @endif
            </p>
        </div>

        <div class="space-y-4">
            @forelse ($questions as $question)
                <div class="rounded-xl border border-neutral-200 px-4 py-4 dark:border-neutral-700">
                    <p class="text-sm font-bold text-brand-700">Q{{ $question->question_number }}</p>
                    <p class="mt-2 text-sm">{{ $question->prompt }}</p>
                    <div class="mt-3 max-w-sm">
                        <input type="text" disabled class="w-full rounded-lg border border-dashed border-brand-400 bg-brand-50 px-3 py-2 text-sm" placeholder="Student answer">
                    </div>
                </div>
            @empty
                <x-ui.empty-state title="No questions">Add questions in the builder to preview them here.</x-ui.empty-state>
            @endforelse
        </div>

        <div class="space-y-2 border-t border-neutral-200 pt-4 dark:border-neutral-700">
            <h3 class="text-sm font-semibold">Answer Key</h3>
            @foreach ($questions as $question)
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
            @endforeach
        </div>
    </div>
</x-ui.card>
