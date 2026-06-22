<div class="grid gap-4 md:grid-cols-2">
    <x-ui.input name="title" label="Title" :value="old('title', $course->title ?? '')" required class="md:col-span-2" />
    <x-ui.input name="slug" label="Slug" :value="old('slug', $course->slug ?? '')" required />
    <x-ui.select name="course_category_id" label="Category">
        <option value="">Uncategorized</option>
        @foreach ($categories as $cat)
            <option value="{{ $cat->id }}" @selected(old('course_category_id', $course->course_category_id ?? '') == $cat->id)>{{ $cat->name }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.select name="exam_type" label="Exam Type">
        @foreach ($examTypes as $type)
            <option value="{{ $type->value }}" @selected(old('exam_type', $course->exam_type->value ?? 'academic') === $type->value)>{{ $type->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.select name="level" label="Level">
        @foreach ($levels as $level)
            <option value="{{ $level->value }}" @selected(old('level', $course->level->value ?? 'intermediate') === $level->value)>{{ $level->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.select name="status" label="Status">
        @foreach ($statuses as $status)
            <option value="{{ $status->value }}" @selected(old('status', $course->status->value ?? 'draft') === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.input name="sort_order" type="number" label="Sort Order" :value="old('sort_order', $course->sort_order ?? 0)" />
    <x-ui.input name="thumbnail_path" label="Thumbnail URL" :value="old('thumbnail_path', $course->thumbnail_path ?? '')" class="md:col-span-2" />
    <x-ui.textarea name="description" label="Description" class="md:col-span-2" rows="4">{{ old('description', $course->description ?? '') }}</x-ui.textarea>
</div>
<div class="mt-6 flex gap-3"><x-ui.button type="submit">{{ $submitLabel }}</x-ui.button><x-ui.button href="{{ route('admin.courses.index') }}" variant="outline">Cancel</x-ui.button></div>
