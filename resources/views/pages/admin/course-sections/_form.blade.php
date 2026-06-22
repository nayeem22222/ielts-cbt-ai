<div class="grid gap-4 md:grid-cols-2">
    <x-ui.select name="course_id" label="Course" class="md:col-span-2">@foreach($courses as $c)<option value="{{ $c->id }}" @selected(old('course_id', $section->course_id ?? '') == $c->id)>{{ $c->title }}</option>@endforeach</x-ui.select>
    <x-ui.input name="title" label="Title" :value="old('title', $section->title ?? '')" required />
    <x-ui.input name="slug" label="Slug" :value="old('slug', $section->slug ?? '')" required />
    <x-ui.select name="status" label="Status">@foreach($statuses as $s)<option value="{{ $s->value }}" @selected(old('status', $section->status->value ?? 'draft') === $s->value)>{{ $s->label() }}</option>@endforeach</x-ui.select>
    <x-ui.input name="sort_order" type="number" label="Sort Order" :value="old('sort_order', $section->sort_order ?? 0)" />
    <x-ui.textarea name="description" label="Description" class="md:col-span-2" rows="3">{{ old('description', $section->description ?? '') }}</x-ui.textarea>
</div>
<div class="mt-6 flex gap-3"><x-ui.button type="submit">{{ $submitLabel }}</x-ui.button><x-ui.button href="{{ route('admin.course-sections.index') }}" variant="outline">Cancel</x-ui.button></div>
