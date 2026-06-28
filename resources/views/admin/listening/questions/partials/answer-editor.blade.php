@php
    $json = old('correct_answer', $question->correct_answer ? json_encode($question->correct_answer, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '[{"value":"","type":"text"}]');
    if (is_array($json)) { $json = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); }
    $requireCorrectAnswer = ! config('listening.questions.allow_draft_without_answer', true);
@endphp
<div class="md:col-span-2">
    @if ($requireCorrectAnswer)
        <x-ui.textarea name="correct_answer" label="Correct Answer (JSON)" rows="5" class="font-mono text-sm" required>{{ $json }}</x-ui.textarea>
    @else
        <x-ui.textarea name="correct_answer" label="Correct Answer (JSON)" rows="5" class="font-mono text-sm">{{ $json }}</x-ui.textarea>
    @endif
</div>
