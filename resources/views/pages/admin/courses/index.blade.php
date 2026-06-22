<x-layouts.admin :title="$entityLabel.'s'" :heading="$entityLabel.'s'" eyebrow="Course Management" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => $entityLabel.'s']]">
    <div class="mb-6 flex justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-neutral-900 dark:text-white">Courses</h2>
            <p class="text-sm aa-muted">Manage IELTS course catalog entries.</p>
        </div>
        @can('create', \App\Models\Course::class)
            <x-ui.button href="{{ route($routePrefix.'.create') }}">Add Course</x-ui.button>
        @endcan
    </div>
    <x-ui.card title="Course Directory">
        <x-admin.crud-toolbar :route-prefix="$routePrefix" :filters="$filters" :sort="$sort" :direction="$direction" :definition="$definition" :statuses="$statuses" show-status-filter>
            <x-slot:customFilters>
                <x-ui.select name="course_category_id" label="Category">
                    <option value="">All categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(($filters['course_category_id'] ?? '') == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.select name="exam_type" label="Exam">
                    <option value="">All types</option>
                    @foreach ($examTypes as $type)
                        <option value="{{ $type->value }}" @selected(($filters['exam_type'] ?? '') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </x-ui.select>
            </x-slot:customFilters>
        </x-admin.crud-toolbar>
        <x-ui.table>
            <thead><tr class="text-left text-xs uppercase aa-muted"><th class="p-4"><input type="checkbox" data-crud-select-all></th><th class="p-4">Title</th><th class="p-4">Category</th><th class="p-4">Exam</th><th class="p-4">Status</th><th class="p-4">Actions</th></tr></thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                @forelse ($records as $record)
                    <tr>
                        <td class="p-4"><input type="checkbox" data-crud-row-checkbox value="{{ $record->id }}"></td>
                        <td class="p-4 font-medium text-neutral-900 dark:text-white">{{ $record->title }}</td>
                        <td class="p-4">{{ $record->category?->name ?? '—' }}</td>
                        <td class="p-4">{{ $record->exam_type->label() }}</td>
                        <td class="p-4"><x-ui.badge tone="blue">{{ $record->status->label() }}</x-ui.badge></td>
                        <td class="p-4 flex gap-2">
                            @can('update', $record)<x-ui.button href="{{ route($routePrefix.'.edit', $record) }}" size="sm" variant="outline">Edit</x-ui.button>@endcan
                            @can('delete', $record)<form method="POST" action="{{ route($routePrefix.'.destroy', $record) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<x-ui.button type="submit" size="sm" variant="danger">Delete</x-ui.button></form>@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-8"><x-ui.empty-state title="No courses">Create a course to get started.</x-ui.empty-state></td></tr>
                @endforelse
            </tbody>
        </x-ui.table>
        <div class="mt-4">{{ $records->links() }}</div>
    </x-ui.card>
</x-layouts.admin>
