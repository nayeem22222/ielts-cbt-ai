<div class="grid gap-4 md:grid-cols-2">
    <x-ui.input name="title" label="Title" :value="old('title', $readingTest->title ?? '')" required class="md:col-span-2" />
    <x-ui.input name="slug" label="Slug" :value="old('slug', $readingTest->slug ?? '')" help="Leave blank to auto-generate from title." />
    <x-ui.select name="exam_type" label="Exam Type">
        @foreach ($examTypes as $type)
            <option value="{{ $type->value }}" @selected(old('exam_type', $readingTest->exam_type?->value ?? 'academic') === $type->value)>{{ $type->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.select name="status" label="Status">
        @foreach ($statuses as $status)
            <option value="{{ $status->value }}" @selected(old('status', $readingTest->status?->value ?? 'draft') === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.input name="duration_minutes" type="number" min="1" max="240" label="Duration (minutes)" :value="old('duration_minutes', $readingTest->duration_minutes ?? 60)" required />
    <x-ui.input name="published_at" type="datetime-local" label="Published At" :value="old('published_at', $readingTest->published_at?->format('Y-m-d\\TH:i') ?? '')" />
    <x-ui.textarea name="instructions" label="Instructions" class="md:col-span-2" rows="4">{{ old('instructions', $readingTest->instructions ?? '') }}</x-ui.textarea>
    <x-ui.textarea name="meta_description" label="Meta Description" class="md:col-span-2" rows="3">{{ old('meta_description', $readingTest->meta_description ?? '') }}</x-ui.textarea>
    <x-ui.textarea name="notes" label="Internal Notes" class="md:col-span-2" rows="4">{{ old('notes', $readingTest->notes ?? '') }}</x-ui.textarea>
</div>
<div class="mt-6 flex gap-3">
    <x-ui.button type="submit">{{ $submitLabel }}</x-ui.button>
    <x-ui.button href="{{ route('admin.reading-tests.index') }}" variant="outline">Cancel</x-ui.button>
</div>
