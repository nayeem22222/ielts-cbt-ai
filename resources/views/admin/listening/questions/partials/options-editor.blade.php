@php
    $json = old('options', $question->options ? json_encode($question->options, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '');
    if (is_array($json)) { $json = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); }
@endphp
<div class="md:col-span-2">
    <x-ui.textarea name="options" label="Options JSON" rows="5" class="font-mono text-sm">{{ $json }}</x-ui.textarea>
</div>
