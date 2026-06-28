@php
    $groupType = $group->question_type?->value ?? 'form_completion';
    $lockedType = old('question_type', $question->question_type?->value ?? $groupType);
    $isMcq = in_array($groupType, ['mcq', 'multiple_answer'], true);
    $isCompletion = str_contains($groupType, 'completion') || $groupType === 'short_answer';
    $usesSimpleAnswer = $isMcq || $isCompletion;
    $questionCount = $group->questions()->count();
@endphp

<div class="space-y-6">
    <x-ui.card title="Basics">
        <div class="grid gap-4 md:grid-cols-2">
            <x-ui.input name="question_number" type="number" min="1" max="40" label="Question Number" :value="old('question_number', $question->question_number)" required />

            <div>
                <p class="mb-2 block text-sm font-medium text-neutral-800 dark:text-neutral-200">Question Type</p>
                <p class="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm dark:border-neutral-800 dark:bg-neutral-900/60">
                    {{ $group->question_type?->label() ?? '—' }}
                    <span class="aa-muted">(from group)</span>
                </p>
                <input type="hidden" name="question_type" value="{{ $lockedType }}">
            </div>

            <div class="md:col-span-2 rounded-xl border border-blue-100 bg-blue-50/60 p-3 text-sm dark:border-blue-900/40 dark:bg-blue-950/20">
                Group Q{{ $group->start_question_number }}–Q{{ $group->end_question_number }} · Section Q{{ $section->start_question_number }}–Q{{ $section->end_question_number }}
            </div>

            <x-ui.textarea name="question_text" label="Question Text (optional)" class="md:col-span-2" rows="2" placeholder="Leave empty for completion blanks defined in the group template.">{{ old('question_text', $question->question_text) }}</x-ui.textarea>
            <x-ui.textarea name="instruction" label="Extra Instruction (optional)" class="md:col-span-2" rows="2">{{ old('instruction', $question->instruction) }}</x-ui.textarea>
        </div>
    </x-ui.card>

    <x-ui.card title="Answer">
        @include('admin.listening.questions.partials.type-specific-answer')
        @unless ($usesSimpleAnswer)
            @include('admin.listening.questions.partials.answer-editor')
        @endunless
        @if ($isCompletion)
            <div class="mt-3">
                <x-ui.input name="word_limit" type="number" min="1" max="10" label="Word Limit (optional override)" :value="old('word_limit', $question->word_limit)" />
            </div>
        @endif
    </x-ui.card>

    <details class="rounded-2xl border border-neutral-200 p-4 dark:border-neutral-800">
        <summary class="cursor-pointer text-sm font-semibold">Scoring &amp; matching rules</summary>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <x-ui.select name="answer_format" label="Answer Format" required>
                @foreach ($answerFormats as $format)
                    <option value="{{ $format->value }}" @selected(old('answer_format', $question->answer_format?->value ?? 'text') === $format->value)>{{ $format->label() }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.input name="marks" type="number" step="0.5" min="0" max="5" label="Marks" :value="old('marks', $question->marks ?? 1)" required />
            <div class="md:col-span-2 grid gap-2 sm:grid-cols-2">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="case_sensitive" value="1" @checked(old('case_sensitive', $question->case_sensitive))> Case sensitive</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="order_sensitive" value="1" @checked(old('order_sensitive', $question->order_sensitive))> Order sensitive</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="allow_plural" value="1" @checked(old('allow_plural', $question->allow_plural ?? true))> Allow plural</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="allow_articles" value="1" @checked(old('allow_articles', $question->allow_articles ?? true))> Allow articles</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="allow_punctuation_variation" value="1" @checked(old('allow_punctuation_variation', $question->allow_punctuation_variation ?? true))> Allow punctuation variation</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_required" value="1" @checked(old('is_required', $question->is_required ?? true))> Required</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $question->is_active ?? true))> Active</label>
            </div>
            @if ($questionCount > 1)
                <x-ui.input name="display_order" type="number" min="1" label="Display Order" :value="old('display_order', $question->display_order)" />
            @endif
        </div>
    </details>

    <details class="rounded-2xl border border-neutral-200 p-4 dark:border-neutral-800">
        <summary class="cursor-pointer text-sm font-semibold">Advanced JSON &amp; audio (optional)</summary>
        <div class="mt-4 space-y-4">
            @unless ($isMcq)
                @include('admin.listening.questions.partials.options-editor')
            @endunless
            @include('admin.listening.questions.partials.accepted-answer-editor')
            @include('admin.listening.questions.partials.timestamp-fields')
        </div>
    </details>
</div>

<div class="mt-6 flex gap-3">
    <x-ui.button type="submit">{{ $submitLabel }}</x-ui.button>
    <x-ui.button href="{{ route($questionsRoutePrefix.'.index', [$listeningTest, $section, $group]) }}" variant="outline">Cancel</x-ui.button>
</div>
