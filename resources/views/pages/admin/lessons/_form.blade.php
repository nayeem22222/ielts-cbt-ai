<div class="grid gap-4 md:grid-cols-2">
    <x-ui.select name="course_section_id" label="Section" class="md:col-span-2">@foreach($sections as $s)<option value="{{ $s->id }}" @selected(old('course_section_id', $lesson->course_section_id ?? '') == $s->id)>{{ $s->course?->title }} — {{ $s->title }}</option>@endforeach</x-ui.select>
    <x-ui.input name="title" label="Title" :value="old('title', $lesson->title ?? '')" required />
    <x-ui.input name="slug" label="Slug" :value="old('slug', $lesson->slug ?? '')" required />
    <x-ui.select name="content_type" label="Content Type">@foreach($contentTypes as $t)<option value="{{ $t->value }}" @selected(old('content_type', $lesson->content_type->value ?? 'video') === $t->value)>{{ $t->label() }}</option>@endforeach</x-ui.select>
    <x-ui.select name="status" label="Status">@foreach($statuses as $s)<option value="{{ $s->value }}" @selected(old('status', $lesson->status->value ?? 'draft') === $s->value)>{{ $s->label() }}</option>@endforeach</x-ui.select>
    <x-ui.input name="video_url" label="Video URL" :value="old('video_url', $lesson->video_url ?? '')" class="md:col-span-2" />
    <x-ui.input name="duration_seconds" type="number" label="Duration (seconds)" :value="old('duration_seconds', $lesson->duration_seconds ?? 0)" />
    <x-ui.input name="sort_order" type="number" label="Sort Order" :value="old('sort_order', $lesson->sort_order ?? 0)" />
    <div class="md:col-span-2"><x-ui.checkbox name="is_preview" value="1" :checked="old('is_preview', $lesson->is_preview ?? false)">Free preview lesson</x-ui.checkbox></div>
    <x-ui.textarea name="description" label="Description" class="md:col-span-2" rows="3">{{ old('description', $lesson->description ?? '') }}</x-ui.textarea>
</div>
<div class="mt-6 flex gap-3"><x-ui.button type="submit">{{ $submitLabel }}</x-ui.button><x-ui.button href="{{ route('admin.lessons.index') }}" variant="outline">Cancel</x-ui.button></div>
