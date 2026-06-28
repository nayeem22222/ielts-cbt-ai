@php
    $options = old('options', $group->options ?? [['key' => 'A', 'text' => '', 'is_correct' => false], ['key' => 'B', 'text' => '', 'is_correct' => false]]);
    if (is_string($options)) { $options = json_decode($options, true) ?: []; }
@endphp
<div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-800" x-data="{ options: @js($options) }">
    <p class="mb-3 text-sm font-medium">MCQ Options</p>
  <template x-for="(option, index) in options" :key="index">
        <div class="mb-2 grid gap-2 sm:grid-cols-12">
            <input type="text" class="aa-input sm:col-span-2" x-model="option.key" placeholder="Key">
            <input type="text" class="aa-input sm:col-span-8" x-model="option.text" placeholder="Option text">
            <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="checkbox" x-model="option.is_correct"> Correct</label>
        </div>
    </template>
    <div class="mt-2 flex gap-2">
        <button type="button" class="text-sm text-blue-600" @click="options.push({ key: String.fromCharCode(65 + options.length), text: '', is_correct: false })">+ Add option</button>
        <button type="button" class="text-sm aa-muted" @click="options.pop()" x-show="options.length > 2">Remove last</button>
    </div>
    <input type="hidden" name="options" :value="JSON.stringify(options)">
    <p class="mt-2 text-xs aa-muted">Fallback: edit raw JSON in group options editor below if needed.</p>
</div>
