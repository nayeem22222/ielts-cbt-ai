@props([
    'test',
    'passage',
    'group',
    'renderer',
])

@php
    $type = $group->question_type;
    $viewKey = $type?->studentRendererViewKey() ?? 'short-answer';
    $questions = $group->questions->filter(fn ($q) => $q->question_number > 0);
    $options = $group->groupOptions;
    $settings = $group->settings ?? [];
@endphp

<div
    class="reading-test-group scroll-mt-6"
    id="question-group-{{ $group->id }}"
    data-group-id="{{ $group->id }}"
    data-question-type="{{ $type?->value }}"
>
    @if ($group->title)
        <h3 class="reading-test-group-title">{{ $group->title }}</h3>
    @endif

    @if ($group->instruction)
        <p class="reading-test-group-instruction">{{ $group->instruction }}</p>
    @endif

    @include('components.reading-test.renderers.'.$viewKey, [
        'test' => $test,
        'passage' => $passage,
        'group' => $group,
        'type' => $type,
        'questions' => $questions,
        'options' => $options,
        'settings' => $settings,
        'renderer' => $renderer,
    ])
</div>
