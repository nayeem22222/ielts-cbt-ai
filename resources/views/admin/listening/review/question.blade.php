<x-layouts.admin :title="'Review Q'.$item['question_number']" heading="Question Review Debug" eyebrow="IELTS CBT">
    @include('admin.listening.review.partials.review-header-admin')

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        @include('admin.listening.review.partials.answer-debug-card')
        @include('admin.listening.review.partials.normalization-debug-card')
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        @include('admin.listening.review.partials.transcript-debug-card')
        @include('admin.listening.review.partials.audio-timestamp-debug-card')
    </div>

    <div class="mt-6 flex gap-3">
        <x-ui.button href="{{ route('admin.listening.results.review.show', $result) }}">Back</x-ui.button>
    </div>
</x-layouts.admin>
