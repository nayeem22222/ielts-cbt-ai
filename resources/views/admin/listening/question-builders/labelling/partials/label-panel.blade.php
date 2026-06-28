<x-ui.card title="Labels & Answers" subtitle="Set question numbers, answers, and save all labels">
    <form method="POST" x-bind:action="saveLabelsUrl" class="space-y-4">
        @csrf
        <input type="hidden" name="confirm_remove" :value="confirmRemove ? 1 : 0">

        @include('admin.listening.question-builders.completion.partials.answer-rule-select', [
            'group' => $group,
            'answerRules' => $answerRules,
            'selectedRule' => $settings['answer_rule'],
            'customRule' => $settings['custom_answer_rule'],
        ])

        <template x-for="(label, index) in labels" :key="'label-form-' + label.question_number">
            <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-900">
                <input type="hidden" :name="'labels['+index+'][question_number]'" :value="label.question_number">
                <input type="hidden" :name="'labels['+index+'][x]'" :value="label.x">
                <input type="hidden" :name="'labels['+index+'][y]'" :value="label.y">

                <div class="mb-3 flex items-center justify-between gap-2">
                    <p class="text-sm font-bold text-brand-700">Question <span x-text="label.question_number"></span></p>
                    <button type="button" class="text-sm text-red-600 hover:underline" @click="removeLabel(index)">Remove</button>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium">Question #</label>
                        <input type="number" class="mt-1 w-full rounded-lg border border-neutral-300 px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900" x-model.number="label.question_number" :min="startQuestion" :max="endQuestion">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Optional Label Text</label>
                        <input type="text" :name="'labels['+index+'][label]'" x-model="label.label" class="mt-1 w-full rounded-lg border border-neutral-300 px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900" placeholder="e.g. pipe">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Correct Answer</label>
                        <input type="text" :name="'labels['+index+'][correct_answer]'" x-model="label.correct_answer" class="mt-1 w-full rounded-lg border border-neutral-300 px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Difficulty</label>
                        <select :name="'labels['+index+'][difficulty]'" x-model="label.difficulty" class="mt-1 w-full rounded-lg border border-neutral-300 px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                            <option value="easy">Easy</option>
                            <option value="medium">Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>
                </div>

                <label class="mt-3 flex items-center gap-2 text-sm">
                    <input type="hidden" :name="'labels['+index+'][case_sensitive]'" value="0">
                    <input type="checkbox" :name="'labels['+index+'][case_sensitive]'" value="1" x-model="label.case_sensitive">
                    <span>Case sensitive</span>
                </label>

                <div class="mt-3">
                    <label class="block text-sm font-medium">Alternative Answers</label>
                    <div class="mt-2 space-y-2">
                        <template x-for="(alt, altIndex) in label.alternative_answers" :key="altIndex">
                            <div class="flex gap-2">
                                <input type="text" :name="'labels['+index+'][alternative_answers]['+altIndex+']'" x-model="label.alternative_answers[altIndex]" class="w-full rounded-lg border border-neutral-300 px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                                <button type="button" class="rounded-lg border px-2 text-sm" @click="label.alternative_answers.splice(altIndex, 1)">×</button>
                            </div>
                        </template>
                        <x-ui.button type="button" size="sm" variant="outline" @click="label.alternative_answers.push('')">Add Alternative</x-ui.button>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="block text-sm font-medium">Explanation</label>
                    <textarea :name="'labels['+index+'][explanation]'" x-model="label.explanation" rows="2" class="mt-1 w-full rounded-lg border border-neutral-300 px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"></textarea>
                </div>

                @include('admin.listening.question-builders.partials.question-reference-fields', ['arrayName' => 'labels'])
            </div>
        </template>

        <template x-if="labels.length === 0">
            <x-ui.empty-state title="No labels yet">Click on the diagram to add your first label.</x-ui.empty-state>
        </template>

        <div class="flex flex-wrap gap-2">
            <x-ui.button type="submit" x-bind:disabled="labels.length === 0">Save Labels</x-ui.button>
            <template x-if="confirmRemove">
                <x-ui.button type="submit" variant="danger">Confirm Remove & Save</x-ui.button>
            </template>
        </div>
    </form>

    <div class="mt-4 space-y-2">
        <template x-for="label in labels.filter((item) => item.question_id)" :key="'delete-' + label.question_id">
            <form
                method="POST"
                :action="`${destroyQuestionBase}/${label.question_id}`"
                onsubmit="return confirm('Delete this saved label and linked question?')"
            >
                @csrf
                @method('DELETE')
                <x-ui.button type="submit" size="sm" variant="danger">
                    Delete saved Q<span x-text="label.question_number"></span>
                </x-ui.button>
            </form>
        </template>
    </div>
</x-ui.card>
