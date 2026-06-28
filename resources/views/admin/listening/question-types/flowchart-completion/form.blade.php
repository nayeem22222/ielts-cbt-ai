@php
    $settings = old('settings', $group->settings ?? ['steps' => [['order' => 1, 'text' => 'Step 1'], ['order' => 2, 'blank' => $group->start_question_number ?? 1]], 'word_limit' => 2, 'direction' => 'vertical']);
    if (is_string($settings)) { $settings = json_decode($settings, true) ?: []; }
@endphp
<div class="md:col-span-2">
    <x-ui.textarea name="settings" label="Flowchart Settings (JSON)" rows="8" class="font-mono text-sm">{{ is_array($settings) ? json_encode($settings, JSON_PRETTY_PRINT) : $settings }}</x-ui.textarea>
</div>
