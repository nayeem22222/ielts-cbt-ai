<x-ui.card title="Add Multiple Choice Question (Multiple Answers)" class="mb-6">
    <form method="POST" action="{{ route('admin.listening-question-groups.objective-questions.store', $group) }}" class="space-y-4" x-data="{ optionCount: 5 }">
        @csrf
        <div class="grid gap-4 md:grid-cols-2">
            <x-ui.input name="question_number" type="number" :min="$group->start_question" :max="$group->end_question" label="Question Number" required />
            <x-ui.select name="difficulty" label="Difficulty">
                <option value="easy">Easy</option>
                <option value="medium" selected>Medium</option>
                <option value="hard">Hard</option>
            </x-ui.select>
        </div>
        <x-ui.textarea name="prompt" label="Question Text" rows="3" required></x-ui.textarea>

        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium">Options</p>
                <button type="button" class="text-sm text-brand-600" @click="optionCount++">+ Add Option</button>
            </div>
            <template x-for="i in optionCount" :key="i">
                <div class="grid gap-2 md:grid-cols-12">
                    <div class="md:col-span-2 flex items-center">
                        <span class="text-sm font-semibold" x-text="String.fromCharCode(64 + i)"></span>
                        <input type="hidden" :name="'options[' + (i-1) + '][option_key]'" :value="String.fromCharCode(64 + i)">
                    </div>
                    <div class="md:col-span-10">
                        <input :name="'options[' + (i-1) + '][option_label]'" required class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                    </div>
                </div>
            </template>
        </div>

        <div>
            <p class="text-sm font-medium mb-2">Correct Answers (select all that apply)</p>
            <div class="flex flex-wrap gap-4">
                @foreach (range(0, 25) as $index)
                    @php $key = chr(65 + $index); @endphp
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="correct_answers[]" value="{{ $key }}">
                        <span>{{ $key }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <x-ui.textarea name="explanation" label="Explanation (optional)" rows="2"></x-ui.textarea>

        @include('admin.listening.question-builders.partials.question-reference-fields')

        <x-ui.button type="submit">Add Question</x-ui.button>
    </form>
</x-ui.card>

@include('admin.listening.question-builders.objective.partials.question-list', [
    'group' => $group,
    'questions' => $questions,
    'statementLabel' => 'Question Text',
    'isMcq' => true,
    'isMultiple' => true,
    'answerChoices' => [],
])
