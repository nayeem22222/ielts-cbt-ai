@php $options = $options ?? []; @endphp
<div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-800" x-data='labellingForm(@json($options))'>
    <p class="text-sm font-medium mb-2">{{ $label ?? 'Map' }} Labelling</p>
    <p class="text-xs aa-muted mb-3">Use image path from group field. Coordinates are percentages (0–100).</p>
    <div class="mb-3 rounded border border-dashed p-4 text-center text-xs aa-muted" x-show="options.image.path">Image: <span x-text="options.image.path"></span></div>
    <p class="text-sm font-medium">Labels</p>
    <template x-for="(label, i) in options.labels" :key="'l'+i">
        <div class="mb-2 grid gap-2 sm:grid-cols-12"><input class="aa-input sm:col-span-2" x-model="label.key"><input class="aa-input sm:col-span-10" x-model="label.text"></div>
    </template>
    <button type="button" class="mb-4 text-sm text-blue-600" @click="options.labels.push({ key: String.fromCharCode(65 + options.labels.length), text: '' })">+ Label</button>
    <p class="text-sm font-medium">Points</p>
    <template x-for="(point, i) in options.points" :key="'p'+i">
        <div class="mb-2 grid gap-2 sm:grid-cols-12">
            <input type="number" class="aa-input sm:col-span-2" x-model.number="point.number" placeholder="#">
            <input type="number" step="0.1" class="aa-input sm:col-span-5" x-model.number="point.x" placeholder="X %">
            <input type="number" step="0.1" class="aa-input sm:col-span-5" x-model.number="point.y" placeholder="Y %">
        </div>
    </template>
    <button type="button" class="text-sm text-blue-600" @click="options.points.push({ number: options.points.length + 1, x: 50, y: 50 })">+ Point</button>
    <input type="hidden" name="options" :value="JSON.stringify(options)">
</div>
<script>function labellingForm(initial){return{options:initial}};</script>
