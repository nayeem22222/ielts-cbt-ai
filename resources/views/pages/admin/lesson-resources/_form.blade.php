<div class="grid gap-4 md:grid-cols-2">
    <x-ui.input name="title" label="Title" :value="old('title', $resource->title ?? '')" required class="md:col-span-2" />
    <x-ui.select name="course_id" label="Course (optional)"><option value="">None</option>@foreach($courses as $c)<option value="{{ $c->id }}" @selected(old('course_id', $resource->course_id ?? '') == $c->id)>{{ $c->title }}</option>@endforeach</x-ui.select>
    <x-ui.select name="lesson_id" label="Lesson (optional)"><option value="">None</option>@foreach($lessons as $l)<option value="{{ $l->id }}" @selected(old('lesson_id', $resource->lesson_id ?? '') == $l->id)>{{ $l->title }}</option>@endforeach</x-ui.select>
    <x-ui.select name="file_type" label="Resource Type">@foreach($resourceTypes as $t)<option value="{{ $t->value }}" @selected(old('file_type', $resource->file_type->value ?? 'pdf') === $t->value)>{{ $t->label() }}</option>@endforeach</x-ui.select>
    <x-ui.input name="sort_order" type="number" label="Sort Order" :value="old('sort_order', $resource->sort_order ?? 0)" />
    <x-ui.input name="file_path" label="File Path" :value="old('file_path', $resource->file_path ?? '')" />
    <x-ui.input name="external_url" label="External URL" :value="old('external_url', $resource->external_url ?? '')" />
    <div class="md:col-span-2"><x-ui.checkbox name="is_downloadable" value="1" :checked="old('is_downloadable', $resource->is_downloadable ?? true)">Allow download</x-ui.checkbox></div>
</div>
<div class="mt-6 flex gap-3"><x-ui.button type="submit">{{ $submitLabel }}</x-ui.button><x-ui.button href="{{ route('admin.lesson-resources.index') }}" variant="outline">Cancel</x-ui.button></div>
