<x-ui.card title="Add Short Answer Question" subtitle="Official IELTS style — question text with word limit rule">
    <form method="POST" action="{{ route('admin.reading-question-groups.short-answer-questions.store', $group) }}" class="space-y-4" x-data="{ answerRule: @js($settings['answer_rule']), customAnswerRule: @js($settings['custom_answer_rule'] ?? '') }">
        @csrf

        <div class="grid gap-4 md:grid-cols-2">
            @include('pages.admin.reading-tests.completion.partials.answer-rule-select', [
                'group' => $group,
                'answerRules' => $answerRules,
                'selectedRule' => $settings['answer_rule'],
                'customRule' => $settings['custom_answer_rule'],
            ])

            <x-ui.input name="question_number" type="number" label="Question #" :min="$group->start_question" :max="$group->end_question" required />
            <x-ui.textarea name="prompt" label="Question Text" class="md:col-span-2" rows="3" required placeholder="What is the main purpose of the research?" />
            <x-ui.input name="correct_answer" label="Correct Answer" required />
            <x-ui.select name="difficulty" label="Difficulty">
                @foreach (['easy', 'medium', 'hard'] as $level)
                    <option value="{{ $level }}" @selected($level === 'medium')>{{ ucfirst($level) }}</option>
                @endforeach
            </x-ui.select>
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="hidden" name="case_sensitive" value="0">
            <input type="checkbox" name="case_sensitive" value="1">
            <span>Case sensitive matching</span>
        </label>

        <div x-data="{ alts: [''] }">
            <label class="block text-sm font-medium">Alternative Answers</label>
            <div class="mt-2 space-y-2">
                <template x-for="(alt, index) in alts" :key="index">
                    <div class="flex gap-2">
                        <input type="text" :name="'alternative_answers['+index+']'" x-model="alts[index]" class="w-full rounded-lg border border-neutral-300 px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                        <button type="button" class="rounded-lg border px-2 text-sm" @click="alts.splice(index, 1)">×</button>
                    </div>
                </template>
                <x-ui.button type="button" size="sm" variant="outline" @click="alts.push('')">Add Alternative</x-ui.button>
            </div>
        </div>

        <x-ui.textarea name="explanation" label="Explanation (optional)" rows="2" />

        @include('pages.admin.reading-tests.partials.question-reference-fields')

        <x-ui.button type="submit">Add Question</x-ui.button>
    </form>
</x-ui.card>
