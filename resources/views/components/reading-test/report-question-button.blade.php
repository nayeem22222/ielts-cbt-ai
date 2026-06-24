@props(['question', 'showNumber' => false])

<button
    type="button"
    class="reading-report-question-btn"
    title="Report a problem with question {{ $question->question_number }}"
    aria-label="Report question {{ $question->question_number }}"
    @click.stop="openTicketModal({{ $question->id }}, {{ $question->question_number }})"
>
    @if ($showNumber)
        <span class="font-semibold">{{ $question->question_number }}.</span>
    @endif
    Report Question
</button>
