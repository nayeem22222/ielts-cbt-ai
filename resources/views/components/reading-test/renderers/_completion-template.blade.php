@php
    $templateHtml = $settings['template_html'] ?? '';
    $questionsByNumber = $renderer->questionsByNumberForGroup($group);
    $interactiveHtml = \App\Support\Reading\CompletionPlaceholderParser::renderInteractiveHtml(
        $templateHtml,
        $test->id,
        $passage->id,
        $group,
        $questionsByNumber,
    );
    $wrapperClass = match ($type->value) {
        'note_completion' => 'reading-test-completion-note',
        'table_completion' => 'reading-test-completion-table',
        'flow_chart_completion' => 'reading-test-completion-flowchart',
        'sentence_completion' => 'reading-test-completion-sentence',
        default => 'reading-test-completion-summary',
    };
@endphp

<div class="{{ $wrapperClass }} reading-test-completion prose max-w-none">
    {!! $interactiveHtml !!}
</div>
