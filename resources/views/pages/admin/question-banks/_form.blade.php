<div class="grid gap-4 md:grid-cols-2">
    <x-ui.input name="name" label="Name" :value="old('name', $questionBank->name ?? '')" required class="md:col-span-2" />
    <x-ui.input name="slug" label="Slug" :value="old('slug', $questionBank->slug ?? '')" required />
    <x-ui.select name="exam_type" label="Exam Type">
        @foreach ($examTypes as $type)
            <option value="{{ $type->value }}" @selected(old('exam_type', isset($questionBank) ? $questionBank->exam_type->value : 'academic') === $type->value)>{{ $type->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.select name="status" label="Status">
        @foreach ($statuses as $status)
            <option value="{{ $status->value }}" @selected(old('status', isset($questionBank) ? $questionBank->status->value : 'draft') === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.textarea name="description" label="Description" class="md:col-span-2" rows="4">{{ old('description', $questionBank->description ?? '') }}</x-ui.textarea>
</div>
<div class="mt-6 flex gap-3">
    <x-ui.button type="submit">{{ $submitLabel }}</x-ui.button>
    <x-ui.button href="{{ route('admin.question-banks.index') }}" variant="outline">Cancel</x-ui.button>
</div>
