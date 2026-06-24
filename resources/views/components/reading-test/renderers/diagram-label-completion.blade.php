@php
    $diagramImageUrl = $renderer->diagramImageUrl($group);
    $labels = $settings['labels'] ?? [];
@endphp

<div class="reading-test-diagram space-y-4">
    @if ($diagramImageUrl)
        <div class="reading-test-diagram-stage relative overflow-hidden rounded-xl border border-neutral-200 bg-neutral-100">
            <img src="{{ $diagramImageUrl }}" alt="Diagram" class="block w-full" />
            @foreach ($labels as $label)
                @php
                    $questionNumber = (int) ($label['question_number'] ?? 0);
                    $question = $questions->firstWhere('question_number', $questionNumber);
                @endphp
                @if ($question)
                    <div
                        class="reading-test-diagram-label absolute -translate-x-1/2 -translate-y-1/2"
                        style="left: {{ (float) ($label['x'] ?? 0) }}%; top: {{ (float) ($label['y'] ?? 0) }}%;"
                    >
                        <span class="reading-test-diagram-badge">{{ $questionNumber }}</span>
                        <x-reading-test.answer-input
                            :test="$test"
                            :passage="$passage"
                            :group="$group"
                            :question="$question"
                            class="reading-test-blank reading-test-diagram-input"
                            placeholder="{{ $label['label'] ?? '' }}"
                        />
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <p class="text-sm text-neutral-500">Diagram image is not available.</p>
    @endif
</div>
