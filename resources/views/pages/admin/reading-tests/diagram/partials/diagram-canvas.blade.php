<x-ui.card title="Diagram Image" subtitle="Select an image, click Upload Image, then click the diagram to place labels">
    <form method="POST" action="{{ route('admin.reading-question-groups.diagram-questions.upload', $group) }}" enctype="multipart/form-data" class="mb-4 space-y-3">
        @csrf
        <div>
            <label class="block text-sm font-medium">Upload Diagram (JPG, PNG, WebP)</label>
            <input
                type="file"
                name="diagram_image"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                class="mt-1 block w-full text-sm"
                required
                @change="previewSelectedFile($event)"
            >
            <p class="mt-1 text-xs aa-muted">Selecting a file shows a local preview. Click <strong>Upload Image</strong> to save it before placing labels.</p>
            @error('diagram_image')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <x-ui.button type="submit" size="sm">Upload Image</x-ui.button>
    </form>

    <template x-if="!displayImageUrl">
        <x-ui.empty-state title="No diagram uploaded">Upload a diagram image to begin placing labels.</x-ui.empty-state>
    </template>

    <div
        x-show="displayImageUrl"
        x-cloak
        class="relative overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-100 dark:border-neutral-700 dark:bg-neutral-900"
    >
        <template x-if="previewImageUrl && !diagramImageUrl">
            <div class="border-b border-amber-200 bg-amber-50 px-4 py-2 text-xs text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">
                Local preview only — upload the image to enable label placement and saving.
            </div>
        </template>

        <div
            class="relative select-none"
            :class="canPlaceLabels ? 'cursor-crosshair' : 'cursor-not-allowed'"
            data-diagram-canvas
            @click="addLabelAtClick($event)"
        >
            <img
                :src="displayImageUrl"
                alt="Diagram preview"
                class="block w-full"
                draggable="false"
                @load="refreshCanvasMetrics()"
            >

            <template x-for="(label, index) in labels" :key="label.question_number + '-' + index">
                <button
                    type="button"
                    class="absolute z-10 flex h-8 w-8 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border-2 border-brand-500 bg-brand-600 text-xs font-bold text-white shadow-lg"
                    :class="selectedIndex === index ? 'ring-2 ring-brand-300' : ''"
                    :style="`left:${label.x}%;top:${label.y}%`"
                    @mousedown.stop.prevent="startDrag(index, $event)"
                    @click.stop="selectLabel(index)"
                    x-text="label.question_number"
                ></button>
            </template>
        </div>
        <p class="border-t border-neutral-200 px-4 py-2 text-xs aa-muted dark:border-neutral-700">
            <span x-show="canPlaceLabels">Click on the diagram to add a label. Drag markers to reposition.</span>
            <span x-show="!canPlaceLabels" x-cloak>Upload the diagram image first, then you can place labels.</span>
        </p>
    </div>
</x-ui.card>
