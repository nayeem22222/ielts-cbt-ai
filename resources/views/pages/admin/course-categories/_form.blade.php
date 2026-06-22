<div class="grid gap-4 md:grid-cols-2">
    <x-ui.input name="name" label="Name" :value="old('name', $category->name ?? '')" required />
    <x-ui.input name="slug" label="Slug" :value="old('slug', $category->slug ?? '')" required />
    <x-ui.select name="parent_id" label="Parent Category">
        <option value="">None</option>
        @foreach ($parents as $parent)
            <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id ?? '') == $parent->id)>{{ $parent->name }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.select name="status" label="Status">
        @foreach ($statuses as $status)
            <option value="{{ $status->value }}" @selected(old('status', $category->status->value ?? 'active') === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.input name="sort_order" type="number" label="Sort Order" :value="old('sort_order', $category->sort_order ?? 0)" />
    <x-ui.textarea name="description" label="Description" class="md:col-span-2" rows="3">{{ old('description', $category->description ?? '') }}</x-ui.textarea>
</div>
<div class="mt-6 flex gap-3">
    <x-ui.button type="submit">{{ $submitLabel }}</x-ui.button>
    <x-ui.button href="{{ route('admin.course-categories.index') }}" variant="outline">Cancel</x-ui.button>
</div>
