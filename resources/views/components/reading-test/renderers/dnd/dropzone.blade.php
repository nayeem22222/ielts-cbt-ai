@props([
    'test',
    'passage',
    'group',
    'question',
    'type',
    'variant' => 'question',
    'placeholder' => 'Drop answer here',
    'showNumber' => false,
])

@php
    $paragraphRef = $question->paragraph_reference ?? $question->reference_paragraph ?? '';
@endphp

<div
    class="reading-dnd-dropzone reading-dnd-dropzone--empty reading-dnd-dropzone--{{ $variant }}"
    data-test-id="{{ $test->id }}"
    data-passage-id="{{ $passage->id }}"
    data-group-id="{{ $group->id }}"
    data-question-id="{{ $question->id }}"
    data-question-number="{{ $question->question_number }}"
    data-question-type="{{ $type->value }}"
    @if ($paragraphRef) data-paragraph-ref="{{ $paragraphRef }}" @endif
    tabindex="0"
    role="button"
    aria-label="{{ $showNumber ? 'Question '.$question->question_number.' drop zone' : 'Drop zone for question '.$question->question_number }}"
>
    <input
        type="hidden"
        class="reading-test-input reading-dnd-input"
        data-test-id="{{ $test->id }}"
        data-passage-id="{{ $passage->id }}"
        data-group-id="{{ $group->id }}"
        data-question-id="{{ $question->id }}"
        data-question-number="{{ $question->question_number }}"
        data-question-type="{{ $type->value }}"
        value=""
    />

    @if ($showNumber)
        <span class="reading-dnd-dropzone__number">{{ $question->question_number }}</span>
    @endif

    <span class="reading-dnd-dropzone__placeholder">{{ $placeholder }}</span>

    <span class="reading-dnd-dropzone__filled" hidden>
        <span class="reading-dnd-dropzone__key"></span>
        <span class="reading-dnd-dropzone__label"></span>
        <button type="button" class="reading-dnd-dropzone__remove" aria-label="Remove answer">&times;</button>
    </span>
</div>
