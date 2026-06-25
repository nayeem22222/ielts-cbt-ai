<x-ui.card title="Add Question" class="mb-6">
    <form method="POST" action="{{ route('admin.reading-question-groups.objective-questions.store', $group) }}" class="grid gap-4 md:grid-cols-2">
        @csrf
        <x-ui.input name="question_number" type="number" :min="$group->start_question" :max="$group->end_question" label="Question Number" required />
        <x-ui.input name="prompt" :label="$statementLabel" class="md:col-span-2" required />
        <div>
            <label class="block text-sm font-medium">Correct Answer</label>
            <select name="correct_answer" required class="mt-1 w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                <option value="">Select answer</option>
                @foreach ($answerChoices as $choice)
                    <option value="{{ $choice }}">{{ str_replace('_', ' ', $choice) }}</option>
                @endforeach
            </select>
        </div>
        <x-ui.select name="difficulty" label="Difficulty">
            <option value="easy">Easy</option>
            <option value="medium" selected>Medium</option>
            <option value="hard">Hard</option>
        </x-ui.select>
        <x-ui.textarea name="explanation" label="Explanation (optional)" class="md:col-span-2" rows="2"></x-ui.textarea>
        <div class="md:col-span-2">
            @include('pages.admin.reading-tests.partials.question-reference-fields')
        </div>
        <div class="md:col-span-2">
            <x-ui.button type="submit">Add Question</x-ui.button>
        </div>
    </form>
</x-ui.card>

@include('pages.admin.reading-tests.objective.partials.question-list', [
    'group' => $group,
    'questions' => $questions,
    'answerChoices' => $answerChoices,
    'statementLabel' => $statementLabel,
    'isMcq' => false,
    'isMultiple' => false,
])
