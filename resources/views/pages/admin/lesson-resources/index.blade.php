<x-layouts.admin title="Resources" heading="Resources" eyebrow="Course Management" :breadcrumbs="[['label'=>'Dashboard','href'=>route('admin.dashboard')],['label'=>'Resources']]">
    <div class="mb-6 flex justify-between"><h2 class="text-xl font-bold text-neutral-900 dark:text-white">Resources</h2>@can('create',\App\Models\LessonResource::class)<x-ui.button href="{{ route($routePrefix.'.create') }}">Add Resource</x-ui.button>@endcan</div>
    <x-ui.card title="Resource Directory">
        <x-admin.crud-toolbar :route-prefix="$routePrefix" :filters="$filters" :sort="$sort" :direction="$direction" :definition="$definition">
            <x-slot:customFilters>
                <x-ui.select name="course_id" label="Course"><option value="">All courses</option>@foreach($courses as $c)<option value="{{ $c->id }}" @selected(($filters['course_id'] ?? '') == $c->id)>{{ $c->title }}</option>@endforeach</x-ui.select>
                <x-ui.select name="file_type" label="Type"><option value="">All types</option>@foreach($resourceTypes as $t)<option value="{{ $t->value }}" @selected(($filters['file_type'] ?? '') === $t->value)>{{ $t->label() }}</option>@endforeach</x-ui.select>
            </x-slot:customFilters>
        </x-admin.crud-toolbar>
        <x-ui.table><thead><tr class="text-left text-xs uppercase aa-muted"><th class="p-4"><input type="checkbox" data-crud-select-all></th><th class="p-4">Title</th><th class="p-4">Type</th><th class="p-4">Course/Lesson</th><th class="p-4">Actions</th></tr></thead>
        <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">@forelse($records as $record)<tr><td class="p-4"><input type="checkbox" data-crud-row-checkbox value="{{ $record->id }}"></td><td class="p-4 font-medium text-neutral-900 dark:text-white">{{ $record->title }}</td><td class="p-4">{{ $record->file_type->label() }}</td><td class="p-4 text-sm aa-muted">{{ $record->lesson?->title ?? $record->course?->title ?? '—' }}</td><td class="p-4 flex gap-2">@can('update',$record)<x-ui.button href="{{ route($routePrefix.'.edit',$record) }}" size="sm" variant="outline">Edit</x-ui.button>@endcan @can('delete',$record)<form method="POST" action="{{ route($routePrefix.'.destroy',$record) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<x-ui.button type="submit" size="sm" variant="danger">Delete</x-ui.button></form>@endcan</td></tr>@empty<tr><td colspan="5" class="p-8"><x-ui.empty-state title="No resources">Upload PDFs, links, or files for courses.</x-ui.empty-state></td></tr>@endforelse</tbody></x-ui.table>
        <div class="mt-4">{{ $records->links() }}</div>
    </x-ui.card>
</x-layouts.admin>
