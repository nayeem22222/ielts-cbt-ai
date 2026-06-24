<x-ui.card title="Sentences" subtitle="Drag to reorder">
    <form id="completion-question-reorder-form" method="POST" action="{{ route('admin.reading-question-groups.completion-questions.reorder', $group) }}">
        @csrf
        <div data-question-ids>
            @foreach ($questions as $question)
                <input type="hidden" name="question_ids[]" value="{{ $question->id }}">
            @endforeach
        </div>
    </form>

    <div id="completion-question-sortable" class="space-y-4">
        @forelse ($questions as $question)
            @php
                $correct = $question->correctAnswers->first();
                $primaryAnswer = $correct?->answer ?? '';
                $alternatives = collect($correct?->answer_json ?? [])->filter(fn ($value) => strcasecmp((string) $value, (string) $primaryAnswer) !== 0)->values();
            @endphp
            <div data-question-item data-question-id="{{ $question->id }}" class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-900">
                <form method="POST" action="{{ route('admin.reading-completion-questions.update', $question) }}" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <div class="grid gap-3 md:grid-cols-2">
                        <x-ui.input name="question_number" type="number" label="Question #" :value="$question->question_number" :min="$group->start_question" :max="$group->end_question" required />
                        <x-ui.select name="difficulty" label="Difficulty">
                            @foreach (['easy', 'medium', 'hard'] as $level)
                                <option value="{{ $level }}" @selected($question->difficulty === $level)>{{ ucfirst($level) }}</option>
                            @endforeach
                        </x-ui.select>
                        <x-ui.textarea name="prompt" label="Sentence" class="md:col-span-2" rows="2" required>{{ $question->prompt }}</x-ui.textarea>
                        <x-ui.input name="correct_answer" label="Correct Answer" :value="$primaryAnswer" required />
                        <div>
                            <label class="block text-sm font-medium">Alternative Answers</label>
                            <div class="mt-2 space-y-2" x-data="{ alts: @js($alternatives->all()) }">
                                <template x-for="(alt, index) in alts" :key="index">
                                    <div class="flex gap-2">
                                        <input type="text" :name="'alternative_answers['+index+']'" x-model="alts[index]" class="w-full rounded-lg border border-neutral-300 px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                                        <button type="button" class="rounded-lg border px-2 text-sm" @click="alts.splice(index, 1)">×</button>
                                    </div>
                                </template>
                                <x-ui.button type="button" size="sm" variant="outline" @click="alts.push('')">Add Alternative</x-ui.button>
                            </div>
                        </div>
                    </div>
                    <x-ui.textarea name="explanation" label="Explanation" rows="2">{{ $question->explanation }}</x-ui.textarea>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button type="submit" size="sm">Save</x-ui.button>
                        <x-ui.button type="button" size="sm" variant="outline" data-question-drag-handle>↕ Reorder</x-ui.button>
                    </div>
                </form>
                <form method="POST" action="{{ route('admin.reading-completion-questions.destroy', $question) }}" class="mt-2" onsubmit="return confirm('Delete question {{ $question->question_number }}?')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" size="sm" variant="danger">Delete</x-ui.button>
                </form>
            </div>
        @empty
            <x-ui.empty-state title="No sentences yet">Add sentences within range Q{{ $group->question_range_label }}.</x-ui.empty-state>
        @endforelse
    </div>
</x-ui.card>
