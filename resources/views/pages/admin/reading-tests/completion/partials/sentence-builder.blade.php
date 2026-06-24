<x-ui.card title="Add Sentence" class="mb-6">
    <form method="POST" action="{{ route('admin.reading-question-groups.completion-questions.store', $group) }}" class="grid gap-4 md:grid-cols-2">
        @csrf
        <x-ui.input name="question_number" type="number" :min="$group->start_question" :max="$group->end_question" label="Question Number" required />
        <x-ui.select name="difficulty" label="Difficulty">
            <option value="easy">Easy</option>
            <option value="medium" selected>Medium</option>
            <option value="hard">Hard</option>
        </x-ui.select>
        <x-ui.textarea name="prompt" label="Sentence Text" class="md:col-span-2" rows="3" placeholder="The first bridge was built in _________." required></x-ui.textarea>
        <x-ui.input name="correct_answer" label="Correct Answer" required />
        <x-ui.input name="alternative_answers[0]" label="Alternative Answer (optional)" />
        <x-ui.textarea name="explanation" label="Explanation (optional)" class="md:col-span-2" rows="2"></x-ui.textarea>
        <div class="md:col-span-2">
            <x-ui.button type="submit">Add Sentence</x-ui.button>
        </div>
    </form>
</x-ui.card>

@include('pages.admin.reading-tests.completion.partials.sentence-list', [
    'group' => $group,
    'questions' => $questions,
])
