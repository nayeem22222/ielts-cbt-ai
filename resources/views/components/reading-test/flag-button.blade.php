@props(['question'])

<button
    type="button"
    class="reading-test-flag-btn"
    title="Flag for review"
    @click.stop="toggleFlag({{ $question->id }}, {{ $question->question_number }})"
    :class="isFlagged({{ $question->id }}) ? 'is-flagged' : ''"
>
    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M5 2v19.5l2-1.5 2 1.5 2-1.5 2 1.5 2-1.5 2 1.5V2H5z"/>
    </svg>
</button>
