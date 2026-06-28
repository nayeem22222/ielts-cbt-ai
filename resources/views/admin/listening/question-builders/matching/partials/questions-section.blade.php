@php
    $correctAnswerLabel = 'Correct '.($group->question_type->usesRomanOptionKeys() ? 'Heading' : 'Answer');
@endphp

<x-ui.card title="Questions" :subtitle="$questionPromptLabel">
    <form method="POST" action="{{ route('admin.listening-question-groups.matching.questions.store', $group) }}" class="mb-5 space-y-3">
        @csrf
        <div class="grid gap-3 md:grid-cols-6">
            <x-ui.input name="question_number" type="number" min="{{ $group->start_question }}" max="{{ $group->end_question }}" label="Question #" required />
            <div class="md:col-span-3">
                <x-ui.input name="prompt" label="{{ $questionPromptLabel }}" required />
            </div>
            @if ($type->requiresParagraphReference())
                <x-ui.input name="paragraph_reference" label="Paragraph Letter" placeholder="A" />
            @endif
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-200">{{ $correctAnswerLabel }}</label>
                <select name="correct_answer" class="mt-1 w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900" @if ($options->isEmpty()) disabled @endif>
                    <option value="">—</option>
                    @foreach ($options as $option)
                        <option value="{{ $option->option_key }}">
                            {{ $option->option_key }}{{ $option->option_label ? ' — '.Str::limit($option->option_label, 40) : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <x-ui.textarea name="explanation" label="Explanation (optional)" rows="2"></x-ui.textarea>
        @include('admin.listening.question-builders.partials.question-reference-fields')
        @if ($options->isEmpty())
            <x-ui.button type="button" disabled>Add Question</x-ui.button>
        @else
            <x-ui.button type="submit">Add Question</x-ui.button>
        @endif
        @if ($options->isEmpty())
            <p class="text-xs text-amber-600 dark:text-amber-400">Add at least one option before creating questions.</p>
        @endif
    </form>

    <form id="matching-question-reorder-form" method="POST" action="{{ route('admin.listening-question-groups.matching.reorder', $group) }}">
        @csrf
        <div data-question-ids>
            @foreach ($questions as $question)
                <input type="hidden" name="question_ids[]" value="{{ $question->id }}">
            @endforeach
        </div>
    </form>

    <div id="matching-question-sortable" class="space-y-3">
        @forelse ($questions as $question)
            @php
                $currentAnswer = $question->correctAnswers->first()?->answer;
            @endphp
            <div data-question-item data-question-id="{{ $question->id }}" class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-900">
                <form method="POST" action="{{ route('admin.listening-questions.update', $question->id) }}" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <div class="grid gap-3 md:grid-cols-6">
                        <x-ui.input name="question_number" type="number" label="Q#" :value="$question->question_number" required />
                        <div class="md:col-span-3">
                            <x-ui.input name="prompt" label="{{ $questionPromptLabel }}" :value="$question->prompt" required />
                        </div>
                        @if ($type->requiresParagraphReference())
                            <x-ui.input name="paragraph_reference" label="Paragraph" :value="$question->paragraph_reference" />
                        @endif
                        <div>
                            <label class="block text-sm font-medium">{{ $correctAnswerLabel }}</label>
                            <select name="correct_answer" class="mt-1 w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                                <option value="">—</option>
                                @foreach ($options as $option)
                                    <option value="{{ $option->option_key }}" @selected($currentAnswer === $option->option_key)>{{ $option->option_key }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <x-ui.textarea name="explanation" label="Explanation" rows="2">{{ $question->explanation }}</x-ui.textarea>
                    @include('admin.listening.question-builders.partials.question-reference-fields', ['question' => $question])
                    <div class="flex gap-2">
                        <x-ui.button type="submit" size="sm">Save Question</x-ui.button>
                        <x-ui.button type="button" size="sm" variant="outline" data-question-drag-handle>↕ Reorder</x-ui.button>
                    </div>
                </form>
                <form method="POST" action="{{ route('admin.listening-questions.destroy', $question->id) }}" class="mt-2" onsubmit="return confirm('Delete question {{ $question->question_number }}?')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" size="sm" variant="danger">Delete Question</x-ui.button>
                </form>
            </div>
        @empty
            <x-ui.empty-state title="No questions yet">Add questions within range Q{{ $group->question_range_label }}.</x-ui.empty-state>
        @endforelse
    </div>
</x-ui.card>
