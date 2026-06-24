<div class="reading-test-short-answer space-y-4">
    @foreach ($questions as $question)
        <div class="reading-test-question-row rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-4" data-question-number="{{ $question->question_number }}">
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm font-bold text-brand-700">Q{{ $question->question_number }}</p>
                <x-reading-test.flag-button :question="$question" />
            </div>
            <p class="mt-2 text-sm">{{ $question->prompt }}</p>
            <div class="mt-3 max-w-sm">
                <x-reading-test.answer-input
                    :test="$test"
                    :passage="$passage"
                    :group="$group"
                    :question="$question"
                    class="reading-test-blank w-full"
                />
            </div>
        </div>
    @endforeach
</div>
