@php
    use App\Support\Reading\CompletionPlaceholderParser;
    use App\Support\Reading\ReadingGroupInteraction;

    $templateHtml = $settings['template_html'] ?? '';
    $questionsByNumber = $renderer->questionsByNumberForGroup($group);
    $interactionMode = ReadingGroupInteraction::mode($group);
    $allowReuse = ReadingGroupInteraction::allowReuse($group);
    $useDragDrop = $interactionMode === ReadingGroupInteraction::MODE_DRAG_DROP && $options->isNotEmpty();
    $useSelect = $interactionMode === ReadingGroupInteraction::MODE_SELECT && $options->isNotEmpty();
    $effectiveMode = $useDragDrop
        ? ReadingGroupInteraction::MODE_DRAG_DROP
        : ($useSelect ? ReadingGroupInteraction::MODE_SELECT : ReadingGroupInteraction::MODE_INPUT);

    $interactiveHtml = CompletionPlaceholderParser::renderInteractiveHtml(
        $templateHtml,
        $test->id,
        $passage->id,
        $group,
        $questionsByNumber,
        $effectiveMode,
    );

    $wrapperClass = match ($type->value) {
        'note_completion' => 'reading-test-completion-note',
        'table_completion' => 'reading-test-completion-table',
        'flow_chart_completion' => 'reading-test-completion-flowchart',
        'sentence_completion' => 'reading-test-completion-sentence',
        default => 'reading-test-completion-summary',
    };
@endphp

@if ($useDragDrop)
    <div
        class="reading-dnd-group reading-test-completion-dnd mb-4"
        data-group-id="{{ $group->id }}"
        data-dnd-type="{{ $type->value }}"
        data-dnd-allow-reuse="{{ $allowReuse ? '1' : '0' }}"
    >
        <h4 class="reading-test-subheading">Options</h4>
        @include('components.reading-test.renderers.dnd.option-pool', [
            'options' => $options,
            'group' => $group,
            'romanKeys' => false,
        ])
    </div>
@endif

<div class="{{ $wrapperClass }} reading-test-completion prose max-w-none">
    {!! $interactiveHtml !!}
</div>

@if ($questions->isNotEmpty())
    <div class="mt-4 flex flex-wrap gap-2 border-t border-neutral-200 pt-3">
        @foreach ($questions as $question)
            <x-reading-test.report-question-button :question="$question" :show-number="true" />
        @endforeach
    </div>
@endif
