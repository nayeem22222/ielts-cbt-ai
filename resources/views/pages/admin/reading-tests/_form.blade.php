<div class="grid gap-4 md:grid-cols-2">
    <x-ui.input name="title" label="Title" :value="old('title', $readingTest->title ?? '')" required class="md:col-span-2" />
    <x-ui.input name="slug" label="Slug" :value="old('slug', $readingTest->slug ?? '')" required />
    <x-ui.select name="exam_type" label="Exam Type">
        @foreach ($examTypes as $type)
            <option value="{{ $type->value }}" @selected(old('exam_type', isset($readingTest) ? $readingTest->exam_type->value : 'academic') === $type->value)>{{ $type->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.select name="status" label="Status">
        @foreach ($statuses as $status)
            <option value="{{ $status->value }}" @selected(old('status', isset($readingTest) ? $readingTest->status->value : 'draft') === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.input name="duration_seconds" type="number" label="Duration (seconds)" :value="old('duration_seconds', $readingTest->duration_seconds ?? 3600)" />
    <x-ui.select name="is_timed" label="Timed Test">
        <option value="1" @selected(old('is_timed', $readingTest->is_timed ?? true))>Yes</option>
        <option value="0" @selected(! old('is_timed', $readingTest->is_timed ?? true))>No</option>
    </x-ui.select>
    <x-ui.textarea name="description" label="Description" class="md:col-span-2" rows="4">{{ old('description', $readingTest->description ?? '') }}</x-ui.textarea>
</div>
<div class="mt-6 flex gap-3">
    <x-ui.button type="submit">{{ $submitLabel }}</x-ui.button>
    <x-ui.button href="{{ route('admin.reading-tests.index') }}" variant="outline">Cancel</x-ui.button>
</div>
