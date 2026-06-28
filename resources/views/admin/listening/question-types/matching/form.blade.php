@php
    $options = old('options', $group->options ?? ['items' => [['key' => '1', 'text' => '']], 'choices' => [['key' => 'A', 'text' => '']], 'allow_choice_reuse' => false]);
    if (is_string($options)) { $options = json_decode($options, true) ?: []; }
@endphp
<div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-800" x-data='matchingForm(@json($options))'>
    <p class="text-sm font-medium mb-2">Items</p>
    <template x-for="(item, i) in options.items" :key="'item'+i">
        <div class="mb-2 grid gap-2 sm:grid-cols-12"><input class="aa-input sm:col-span-2" x-model="item.key"><input class="aa-input sm:col-span-10" x-model="item.text"></div>
    </template>
    <button type="button" class="mb-4 text-sm text-blue-600" @click="options.items.push({ key: String(options.items.length + 1), text: '' })">+ Item</button>
    <p class="text-sm font-medium mb-2">Choices</p>
    <template x-for="(choice, i) in options.choices" :key="'choice'+i">
        <div class="mb-2 grid gap-2 sm:grid-cols-12"><input class="aa-input sm:col-span-2" x-model="choice.key"><input class="aa-input sm:col-span-10" x-model="choice.text"></div>
    </template>
    <button type="button" class="text-sm text-blue-600" @click="options.choices.push({ key: String.fromCharCode(65 + options.choices.length), text: '' })">+ Choice</button>
    <label class="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" x-model="options.allow_choice_reuse"> Allow choice reuse</label>
    <input type="hidden" name="options" :value="JSON.stringify(options)">
</div>
<script>
function matchingForm(initial) {
    return { options: initial };
}
</script>
