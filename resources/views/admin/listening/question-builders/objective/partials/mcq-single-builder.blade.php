<x-ui.card title="Add Multiple Choice Question" class="mb-6">
    <form
        method="POST"
        action="{{ route('admin.listening-question-groups.objective-questions.store', $group) }}"
        class="space-y-4"
        x-data="{
            optionCount: 3,
            correctAnswer: '',
            optionLabel(i) {
                return String.fromCharCode(64 + i);
            },
            addOption() {
                this.optionCount++;
            },
            removeLastOption() {
                if (this.optionCount <= 3) {
                    return;
                }

                const removedKey = this.optionLabel(this.optionCount);

                if (this.correctAnswer === removedKey) {
                    this.correctAnswer = '';
                }

                this.optionCount--;
            },
        }"
    >
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
                <button type="button" class="text-sm text-brand-600" @click="addOption()">+ Add Option</button>
            </div>
            <template x-for="i in optionCount" :key="i">
                <div class="grid gap-2 md:grid-cols-12">
                    <div class="md:col-span-2 flex items-center gap-2">
                        <span class="text-sm font-semibold" x-text="optionLabel(i)"></span>
                        <input type="hidden" :name="'options[' + (i - 1) + '][option_key]'" :value="optionLabel(i)">
                    </div>
                    <div class="md:col-span-10 flex items-center gap-2">
                        <input
                            :name="'options[' + (i - 1) + '][option_label]'"
                            required
                            class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                            :placeholder="'Option ' + optionLabel(i)"
                        >
                        <button
                            type="button"
                            class="shrink-0 text-xs font-medium text-red-600 hover:text-red-700"
                            x-show="optionCount > 3 && i === optionCount"
                            @click="removeLastOption()"
                        >
                            Remove
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <div>
            <label class="block text-sm font-medium">Correct Answer</label>
            <select
                name="correct_answer"
                required
                x-model="correctAnswer"
                class="mt-1 w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
            >
                <option value="">Select correct option</option>
                <template x-for="i in optionCount" :key="'correct-' + i">
                    <option :value="optionLabel(i)" x-text="optionLabel(i)"></option>
                </template>
            </select>
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
    'isMultiple' => false,
    'answerChoices' => [],
])
