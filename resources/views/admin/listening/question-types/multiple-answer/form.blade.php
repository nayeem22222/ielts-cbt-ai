@php
    $options = old('options', $group->options ?? [['key' => 'A', 'text' => ''], ['key' => 'B', 'text' => ''], ['key' => 'C', 'text' => '']]);
    $settings = old('settings', $group->settings ?? ['required_answers' => 2, 'display_instruction' => 'Choose TWO letters, A-E.', 'partial_marking' => false]);
    if (is_string($options)) { $options = json_decode($options, true) ?: []; }
    if (is_string($settings)) { $settings = json_decode($settings, true) ?: []; }
@endphp
<div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-800" x-data="{ options: @js($options), settings: @js($settings) }">
    <x-ui.input name="settings_required_answers" type="number" min="1" label="Required Answers Count" x-model.number="settings.required_answers" class="mb-3" />
    <x-ui.input name="settings_display_instruction" label="Display Instruction" x-model="settings.display_instruction" class="mb-3" />
    <template x-for="(option, index) in options" :key="index">
        <div class="mb-2 grid gap-2 sm:grid-cols-12">
            <input type="text" class="aa-input sm:col-span-2" x-model="option.key">
            <input type="text" class="aa-input sm:col-span-10" x-model="option.text">
        </div>
    </template>
    <button type="button" class="text-sm text-blue-600" @click="options.push({ key: String.fromCharCode(65 + options.length), text: '' })">+ Add option</button>
    <input type="hidden" name="options" :value="JSON.stringify(options)">
    <input type="hidden" name="settings" :value="JSON.stringify(settings)">
</div>
