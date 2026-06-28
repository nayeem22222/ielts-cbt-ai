@php
    $rule = collect($answerRules)->first(fn ($item) => $item->value === ($settings['answer_rule'] ?? ''));
@endphp

<x-ui.card title="Admin Preview" subtitle="Diagram with numbered labels">
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

        @if ($diagramImageUrl)
            <div class="relative overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-100 dark:border-neutral-700 dark:bg-neutral-900">
                <img src="{{ $diagramImageUrl }}" alt="Diagram preview" class="block w-full">
                @foreach ($settings['labels'] ?? [] as $label)
                    <span
                        class="absolute flex h-8 w-8 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border-2 border-brand-500 bg-brand-600 text-xs font-bold text-white shadow"
                        style="left: {{ (float) ($label['x'] ?? 0) }}%; top: {{ (float) ($label['y'] ?? 0) }}%;"
                    >{{ (int) ($label['question_number'] ?? 0) }}</span>
                @endforeach
            </div>
        @else
            <x-ui.empty-state title="No diagram image">Upload a diagram in the builder to preview labels.</x-ui.empty-state>
        @endif

        <div class="space-y-2">
            <h3 class="text-sm font-semibold">Answer Key</h3>
            @forelse ($questions as $question)
                @php
                    $correct = $question->correctAnswers->first();
                    $alternatives = $question->alternativeAnswers;
                @endphp
                <div class="rounded-xl border border-neutral-200 px-4 py-3 text-sm dark:border-neutral-700">
                    <span class="font-bold text-brand-700">Q{{ $question->question_number }}</span>
                    @if ($question->metadata['label'] ?? null)
                        <span class="aa-muted"> ({{ $question->metadata['label'] }})</span>
                    @endif
                    <span class="mx-2">→</span>
                    <span>{{ $correct?->answer ?: '—' }}</span>
                    @if ($alternatives !== [])
                        <span class="aa-muted"> (also: {{ implode(', ', $alternatives) }})</span>
                    @endif
                </div>
            @empty
                <p class="text-sm aa-muted">No labels saved yet.</p>
            @endforelse
        </div>
    </div>
</x-ui.card>
