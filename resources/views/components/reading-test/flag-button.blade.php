@props(['question'])

<button
    type="button"
    class="reading-test-flag-btn"
    :title="isFlagged({{ $question->id }}) ? 'Remove flag' : 'Flag for review'"
    :aria-label="isFlagged({{ $question->id }}) ? 'Remove flag from question {{ $question->question_number }}' : 'Flag question {{ $question->question_number }} for review'"
    @click.stop="toggleFlag({{ $question->id }}, {{ $question->question_number }})"
    :class="isFlagged({{ $question->id }}) ? 'is-flagged' : ''"
    :disabled="isLocked"
>
    <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M5 2v19.5l2-1.5 2 1.5 2-1.5 2 1.5 2-1.5 2 1.5V2H5z"/>
    </svg>
    <span class="reading-test-flag-btn__label" x-text="isFlagged({{ $question->id }}) ? 'Flagged' : 'Flag'"></span>
</button>
