<x-ui.card title="Questions" subtitle="Drag to reorder">
    <form id="objective-question-reorder-form" method="POST" action="{{ route('admin.reading-question-groups.objective-questions.reorder', $group) }}">
        @csrf
        <div data-question-ids>
            @foreach ($questions as $question)
                <input type="hidden" name="question_ids[]" value="{{ $question->id }}">
            @endforeach
        </div>
    </form>

    <div id="objective-question-sortable" class="space-y-4">
        @forelse ($questions as $question)
            @php
                $currentAnswer = $question->correctAnswers->first()?->answer;
                $displayNumber = $question->question_number > 0 ? $question->question_number : '';
                $isDraft = $question->question_number === 0;
            @endphp
            <div data-question-item data-question-id="{{ $question->id }}" @class(['rounded-xl border p-4', 'border-amber-300 bg-amber-50/40 dark:border-amber-700' => $isDraft, 'border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900' => ! $isDraft])>
                @if ($isDraft)
                    <p class="mb-3 text-xs font-medium text-amber-700 dark:text-amber-300">Duplicated draft — assign a question number and save.</p>
                @endif

                <form method="POST" action="{{ route('admin.reading-objective-questions.update', $question) }}" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <div class="grid gap-3 md:grid-cols-2">
                        <x-ui.input name="question_number" type="number" label="Question #" :value="$displayNumber" :min="$group->start_question" :max="$group->end_question" required />
                        <x-ui.select name="difficulty" label="Difficulty">
                            @foreach (['easy', 'medium', 'hard'] as $level)
                                <option value="{{ $level }}" @selected($question->difficulty === $level)>{{ ucfirst($level) }}</option>
                            @endforeach
                        </x-ui.select>
                        <div class="md:col-span-2">
                            <x-ui.input name="prompt" :label="$statementLabel ?? 'Question'" :value="$question->prompt" required />
                        </div>

                        @if ($isMcq ?? false)
                            <div class="md:col-span-2 space-y-2">
                                <p class="text-sm font-medium">Options</p>
                                @foreach ($question->options as $optIndex => $option)
                                    <div class="grid gap-2 md:grid-cols-12">
                                        <div class="md:col-span-2">
                                            <input name="options[{{ $optIndex }}][option_key]" value="{{ $option->option_key }}" class="w-full rounded-lg border border-neutral-300 px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                                        </div>
                                        <div class="md:col-span-10">
                                            <input name="options[{{ $optIndex }}][option_label]" value="{{ $option->option_label }}" required class="w-full rounded-lg border border-neutral-300 px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium">Correct Answer</label>
                            @if ($isMultiple ?? false)
                                @php $selected = $question->correctAnswers->first()?->answer_json ?? []; @endphp
                                <div class="mt-2 space-y-1">
                                    @foreach ($question->options as $option)
                                        <label class="flex items-center gap-2 text-sm">
                                            <input type="checkbox" name="correct_answers[]" value="{{ $option->option_key }}" @checked(in_array($option->option_key, $selected, true))>
                                            <span>{{ $option->option_key }} — {{ Str::limit($option->option_label, 60) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @elseif ($isMcq ?? false)
                                <select name="correct_answer" required class="mt-1 w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                                    @foreach ($question->options as $option)
                                        <option value="{{ $option->option_key }}" @selected($currentAnswer === $option->option_key)>{{ $option->option_key }} — {{ Str::limit($option->option_label, 40) }}</option>
                                    @endforeach
                                </select>
                            @else
                                <select name="correct_answer" required class="mt-1 w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                                    @foreach ($answerChoices as $choice)
                                        <option value="{{ $choice }}" @selected($currentAnswer === $choice)>{{ str_replace('_', ' ', $choice) }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    </div>
                    <x-ui.textarea name="explanation" label="Explanation" rows="2">{{ $question->explanation }}</x-ui.textarea>
                    @include('pages.admin.reading-tests.partials.question-reference-fields', ['question' => $question])
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button type="submit" size="sm">Save</x-ui.button>
                        <x-ui.button type="button" size="sm" variant="outline" data-question-drag-handle>↕ Reorder</x-ui.button>
                    </div>
                </form>

                <div class="mt-2 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('admin.reading-objective-questions.duplicate', $question) }}">
                        @csrf
                        <x-ui.button type="submit" size="sm" variant="outline">Duplicate</x-ui.button>
                    </form>
                    <form method="POST" action="{{ route('admin.reading-objective-questions.destroy', $question) }}" onsubmit="return confirm('Delete question{{ $displayNumber ? ' '.$displayNumber : '' }}?')">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="submit" size="sm" variant="danger">Delete</x-ui.button>
                    </form>
                </div>
            </div>
        @empty
            <x-ui.empty-state title="No questions yet">Add questions within range Q{{ $group->question_range_label }}.</x-ui.empty-state>
        @endforelse
    </div>
</x-ui.card>
