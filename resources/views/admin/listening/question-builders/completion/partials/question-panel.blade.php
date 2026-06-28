<x-ui.card title="Detected Questions" subtitle="Auto-generated from template placeholders">
    <form id="completion-question-reorder-form" method="POST" action="{{ route('admin.listening-question-groups.completion-questions.reorder', $group) }}">
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
                $alternatives = $question->alternativeAnswers;
                $caseSensitive = $question->case_sensitive;
                $label = $question->metadata['placeholder_label'] ?? null;
            @endphp
            <div data-question-item data-question-id="{{ $question->id }}" class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-900">
                <form method="POST" action="{{ route('admin.listening-completion-questions.update', $question->id) }}" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="question_number" value="{{ $question->question_number }}">
                    <input type="hidden" name="prompt" value="{{ $question->prompt }}">

                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <p class="text-sm font-bold text-brand-700">Question {{ $question->question_number }}</p>
                            @if ($label)
                                <p class="text-xs aa-muted">Label: {{ $label }}</p>
                            @elseif ($question->prompt)
                                <p class="text-xs aa-muted">{{ Str::limit($question->prompt, 80) }}</p>
                            @endif
                        </div>
                        <x-ui.button type="button" size="sm" variant="outline" data-question-drag-handle>↕ Reorder</x-ui.button>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <x-ui.input name="correct_answer" label="Correct Answer" :value="$primaryAnswer" required />
                        <x-ui.select name="difficulty" label="Difficulty">
                            @foreach (['easy', 'medium', 'hard'] as $level)
                                <option value="{{ $level }}" @selected($question->difficulty === $level)>{{ ucfirst($level) }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="case_sensitive" value="0">
                        <input type="checkbox" name="case_sensitive" value="1" @checked($caseSensitive)>
                        <span>Case sensitive matching</span>
                    </label>

                    <div>
                        <label class="block text-sm font-medium">Alternative Answers</label>
                        <div class="mt-2 space-y-2" x-data="{ alts: @js($alternatives) }">
                            <template x-for="(alt, index) in alts" :key="index">
                                <div class="flex gap-2">
                                    <input type="text" :name="'alternative_answers['+index+']'" x-model="alts[index]" class="w-full rounded-lg border border-neutral-300 px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                                    <button type="button" class="rounded-lg border px-2 text-sm" @click="alts.splice(index, 1)">×</button>
                                </div>
                            </template>
                            <x-ui.button type="button" size="sm" variant="outline" @click="alts.push('')">Add Alternative</x-ui.button>
                        </div>
                    </div>

                    <x-ui.textarea name="explanation" label="Explanation (optional)" rows="2">{{ $question->explanation }}</x-ui.textarea>

                    @include('admin.listening.question-builders.partials.question-reference-fields', ['question' => $question])

                    <x-ui.button type="submit" size="sm">Save Answer</x-ui.button>
                </form>

                <form method="POST" action="{{ route('admin.listening-completion-questions.destroy', $question->id) }}" class="mt-2" onsubmit="return confirm('Delete question {{ $question->question_number }}?')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" size="sm" variant="danger">Delete</x-ui.button>
                </form>
            </div>
        @empty
            <x-ui.empty-state title="No blanks detected yet">Save a template with placeholders to auto-generate questions.</x-ui.empty-state>
        @endforelse
    </div>
</x-ui.card>
