<x-ui.card title="Sentence Completion" class="mb-6">
    <div x-data="{ mode: 'manual' }" class="space-y-6">
        <div class="flex flex-wrap gap-2">
            <button type="button" class="rounded-xl px-3 py-1.5 text-sm font-medium" :class="mode === 'manual' ? 'bg-brand-600 text-white' : 'border border-neutral-300'" @click="mode = 'manual'">Manual Rows</button>
            <button type="button" class="rounded-xl px-3 py-1.5 text-sm font-medium" :class="mode === 'template' ? 'bg-brand-600 text-white' : 'border border-neutral-300'" @click="mode = 'template'">Template Mode</button>
        </div>

        <div x-show="mode === 'manual'" class="grid gap-6 xl:grid-cols-2">
            <div>
                <form method="POST" action="{{ route('admin.reading-question-groups.completion-questions.store', $group) }}" class="grid gap-4 md:grid-cols-2">
                    @csrf
                    <x-ui.input name="question_number" type="number" :min="$group->start_question" :max="$group->end_question" label="Question Number" required />
                    <x-ui.select name="difficulty" label="Difficulty">
                        <option value="easy">Easy</option>
                        <option value="medium" selected>Medium</option>
                        <option value="hard">Hard</option>
                    </x-ui.select>
                    <x-ui.textarea name="sentence_before" label="Text Before Blank" class="md:col-span-2" rows="2" placeholder="The first bridge was built in"></x-ui.textarea>
                    <x-ui.textarea name="sentence_after" label="Text After Blank" class="md:col-span-2" rows="2" placeholder="during the nineteenth century."></x-ui.textarea>
                    <x-ui.input name="correct_answer" label="Correct Answer" required />
                    <div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="case_sensitive" value="1">
                            <span>Case sensitive</span>
                        </label>
                    </div>
                    <x-ui.input name="alternative_answers[0]" label="Alternative Answer (optional)" class="md:col-span-2" />
                    <x-ui.textarea name="explanation" label="Explanation (optional)" class="md:col-span-2" rows="2"></x-ui.textarea>

                    <div class="md:col-span-2">
                        @include('pages.admin.reading-tests.partials.question-reference-fields')
                    </div>

                    <div class="md:col-span-2">
                        <x-ui.button type="submit">Add Sentence</x-ui.button>
                    </div>
                </form>
            </div>
            <div>
                @include('pages.admin.reading-tests.completion.partials.sentence-list', [
                    'group' => $group,
                    'questions' => $questions,
                ])
            </div>
        </div>

        <div x-show="mode === 'template'" x-cloak>
            @include('pages.admin.reading-tests.completion.partials.template-builder', [
                'group' => $group,
                'settings' => $settings,
                'answerRules' => $answerRules,
                'questions' => $questions,
                'editorLabel' => 'Sentence Template',
                'editorId' => 'completion_sentence_template_html',
            ])
        </div>
    </div>
</x-ui.card>
