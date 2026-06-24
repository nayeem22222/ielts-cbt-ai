@props([
    'test',
    'passage',
    'group',
    'question',
    'type' => null,
    'inputType' => 'text',
])

@php
    $questionType = $type ?? $group->question_type?->value ?? '';
@endphp

<input
    type="{{ $inputType }}"
    {{ $attributes->merge([
        'class' => 'reading-test-input '.$attributes->get('class', ''),
        'data-test-id' => (string) $test->id,
        'data-passage-id' => (string) $passage->id,
        'data-group-id' => (string) $group->id,
        'data-question-id' => (string) $question->id,
        'data-question-number' => (string) $question->question_number,
        'data-question-type' => $questionType,
        'autocomplete' => 'off',
        'spellcheck' => 'false',
    ]) }}
/>
