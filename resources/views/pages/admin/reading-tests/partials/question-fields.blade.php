@props([
    'questionTypes',
    'question' => null,
    'prefix' => '',
])

@php
    $selectedType = old('type', $question?->type?->value ?? \App\Enums\Exam\ReadingQuestionType::MultipleChoiceSingle->value);
    $options = old('options', $question?->options?->pluck('option_text')->all() ?? ['', '', '', '']);
    $correctAnswer = old('correct_answer', $question?->correctAnswer?->answer_value ?? '');
@endphp

<div class="grid gap-4 md:grid-cols-2">
    <x-ui.select name="type" label="Question Type" class="md:col-span-2">
        @foreach ($questionTypes as $type)
            <option value="{{ $type->value }}" @selected($selectedType === $type->value)>{{ $type->label() }}</option>
        @endforeach
    </x-ui.select>

    <x-ui.input name="question_number" type="number" label="Question Number" :value="old('question_number', $question?->question_number ?? 1)" required />
    <x-ui.input name="marks" type="number" step="0.5" label="Marks" :value="old('marks', $question?->marks ?? 1)" />
    <x-ui.select name="difficulty" label="Difficulty">
        @foreach (['easy', 'medium', 'hard'] as $level)
            <option value="{{ $level }}" @selected(old('difficulty', $question?->difficulty ?? 'medium') === $level)>{{ ucfirst($level) }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.input name="sort_order" type="number" label="Sort Order" :value="old('sort_order', $question?->sort_order ?? 1)" />

    <div class="md:col-span-2">
        <x-ui.rich-editor name="prompt" label="Question Prompt" :value="old('prompt', $question?->prompt ?? '')" required />
    </div>

    <div class="md:col-span-2">
        <x-ui.rich-editor name="stimulus" label="Additional Stimulus (optional)" :value="old('stimulus', is_array($question?->stimulus) ? json_encode($question->stimulus) : ($question?->stimulus ?? ''))" />
    </div>

    <div class="md:col-span-2 rounded-2xl border border-neutral-200 p-4 dark:border-neutral-800">
        <p class="mb-3 text-sm font-semibold text-neutral-900 dark:text-white">Answer Configuration</p>
        <p class="mb-4 text-xs aa-muted">Supports all 15 official IELTS reading types: options for MCQ/matching/T-F-NG; text answers for completion and short answer types.</p>

        <div class="grid gap-3 md:grid-cols-2">
            @for ($i = 0; $i < max(4, count($options)); $i++)
                <x-ui.input name="options[{{ $i }}]" label="Option {{ chr(65 + $i) }}" :value="$options[$i] ?? ''" />
            @endfor
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <x-ui.input name="correct_answer" label="Correct Answer" :value="$correctAnswer" placeholder="e.g. B, True, keyword" />
            <x-ui.textarea name="explanation" label="Explanation" rows="3">{{ old('explanation', $question?->explanation?->explanation ?? '') }}</x-ui.textarea>
        </div>
    </div>
</div>
