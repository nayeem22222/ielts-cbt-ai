<x-layouts.dashboard :title="'Question '.$item['question_number']" heading="Question Review">
    @include('student.listening.review.partials.review-header')

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        @include('student.listening.review.partials.answer-comparison')
        @include('student.listening.review.partials.explanation-card')
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        @include('student.listening.review.partials.transcript-highlight')
        @include('student.listening.review.partials.audio-review-player')
    </div>

    <div class="mt-6 flex gap-3">
        @if ($prevQuestion)
            <x-ui.button variant="secondary" href="{{ route('student.listening.results.review.question', [$result, $prevQuestion]) }}">Previous</x-ui.button>
        @endif
        @if ($nextQuestion)
            <x-ui.button variant="secondary" href="{{ route('student.listening.results.review.question', [$result, $nextQuestion]) }}">Next</x-ui.button>
        @endif
        <x-ui.button href="{{ route('student.listening.results.review.show', $result) }}">All questions</x-ui.button>
    </div>

    @vite(['resources/js/listening/review-transcript-highlight.js', 'resources/js/listening/review-audio-player.js', 'resources/js/listening/review-navigation.js'])
</x-layouts.dashboard>
