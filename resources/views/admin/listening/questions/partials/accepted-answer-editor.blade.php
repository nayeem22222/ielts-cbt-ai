@php
    $json = old('accepted_answers', $question->accepted_answers ? json_encode($question->accepted_answers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '[]');
    if (is_array($json)) { $json = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); }
@endphp
<div class="md:col-span-2">
    <x-ui.textarea name="accepted_answers" label="Accepted Answers (JSON)" rows="4" class="font-mono text-sm">{{ $json }}</x-ui.textarea>
</div>
