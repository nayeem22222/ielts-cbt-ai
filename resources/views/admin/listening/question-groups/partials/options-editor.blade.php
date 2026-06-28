@php
    $optionsJson = old('options', $group->options ? json_encode($group->options, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '');
    if (is_array($optionsJson)) { $optionsJson = json_encode($optionsJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); }
    $settingsJson = old('settings', $group->settings ? json_encode($group->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '');
    if (is_array($settingsJson)) { $settingsJson = json_encode($settingsJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); }
@endphp
<div class="md:col-span-2">
    <x-ui.textarea name="options" label="Options JSON" rows="6" class="font-mono text-sm">{{ $optionsJson }}</x-ui.textarea>
</div>
<div class="md:col-span-2">
    <x-ui.textarea name="settings" label="Settings JSON" rows="4" class="font-mono text-sm">{{ $settingsJson }}</x-ui.textarea>
</div>
