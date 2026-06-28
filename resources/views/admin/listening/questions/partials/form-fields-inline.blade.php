@php
    $groupType = $group->question_type?->value ?? 'form_completion';
    $isMcq = in_array($groupType, ['mcq', 'multiple_answer'], true);
    $isCompletion = str_contains($groupType, 'completion') || $groupType === 'short_answer';
    $usesSimpleAnswer = $isMcq || $isCompletion;
    $suggestedNumber = old('question_number');
    if ($suggestedNumber === null) {
        $max = $questions->max('question_number');
        $suggestedNumber = $max !== null ? ((int) $max + 1) : $group->start_question_number;
    }
    if ((int) $suggestedNumber > (int) $group->end_question_number) {
        $suggestedNumber = null;
    }
@endphp

<div class="grid gap-3 md:grid-cols-2">
    <x-ui.input name="question_number" type="number" min="{{ $group->start_question_number }}" max="{{ $group->end_question_number }}" label="Question #" :value="$suggestedNumber" required />
    <input type="hidden" name="question_type" value="{{ $groupType }}">
    <input type="hidden" name="answer_format" value="text">
    <input type="hidden" name="marks" value="1">
    <input type="hidden" name="is_active" value="1">
    <input type="hidden" name="is_required" value="1">

    <div class="md:col-span-2">
        <x-ui.textarea name="question_text" label="Question Text (optional)" rows="2" placeholder="Leave empty for completion blanks from group template.">{{ old('question_text') }}</x-ui.textarea>
    </div>

    <div class="md:col-span-2">
        @include('admin.listening.questions.partials.type-specific-answer', ['question' => new \App\Models\Listening\ListeningQuestion()])
    </div>

    @if ($isCompletion)
        <x-ui.input name="word_limit" type="number" min="1" max="10" label="Word Limit" :value="old('word_limit', $group->settings['word_limit'] ?? 2)" />
    @endif
</div>

@if ($errors->any())
    <x-ui.alert tone="red" class="mt-3">
        <ul class="list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-ui.alert>
@endif
