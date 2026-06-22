<x-layouts.admin
    :title="$entityLabel.'s'"
    :heading="$entityLabel.'s'"
    eyebrow="Course Management"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard')],
        ['label' => $entityLabel.'s'],
    ]"
>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-neutral-900 dark:text-white">{{ $entityLabel }}s</h2>
            <p class="text-sm aa-muted">Search, paginate, export, and manage {{ strtolower($entityLabel) }} records.</p>
        </div>
        @can('create', \App\Models\CourseCategory::class)
            <x-ui.button href="{{ route($routePrefix.'.create') }}">Add Category</x-ui.button>
        @endcan
    </div>

    <x-ui.card :title="$entityLabel.' Directory'">
        <x-admin.crud-toolbar
            :route-prefix="$routePrefix"
            :filters="$filters"
            :sort="$sort"
            :direction="$direction"
            :definition="$definition"
            :statuses="$statuses ?? []"
            :show-status-filter="isset($statuses)"
        >
            <x-slot:customFilters>
                @if (isset($parents))
                    <x-ui.select name="parent_id" label="Parent">
                        <option value="">All parents</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}" @selected(($filters['parent_id'] ?? '') == $parent->id)>{{ $parent->name }}</option>
                        @endforeach
                    </x-ui.select>
                @endif
            </x-slot:customFilters>
        </x-admin.crud-toolbar>

        <x-ui.table>
            <thead>
                <tr class="text-left text-xs uppercase aa-muted">
                    <th class="p-4"><input type="checkbox" data-crud-select-all></th>
                    <th class="p-4">Name</th>
                    <th class="p-4">Slug</th>
                    <th class="p-4">Parent</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Order</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                @forelse ($records as $record)
                    <tr>
                        <td class="p-4"><input type="checkbox" data-crud-row-checkbox value="{{ $record->id }}"></td>
                        <td class="p-4 font-medium text-neutral-900 dark:text-white">{{ $record->name }}</td>
                        <td class="p-4 text-sm aa-muted">{{ $record->slug }}</td>
                        <td class="p-4">{{ $record->parent?->name ?? '—' }}</td>
                        <td class="p-4"><x-ui.badge tone="blue">{{ $record->status->label() }}</x-ui.badge></td>
                        <td class="p-4">{{ $record->sort_order }}</td>
                        <td class="p-4">
                            <div class="flex gap-2">
                                @can('update', $record)
                                    <x-ui.button href="{{ route($routePrefix.'.edit', $record) }}" size="sm" variant="outline">Edit</x-ui.button>
                                @endcan
                                @can('delete', $record)
                                    <form method="POST" action="{{ route($routePrefix.'.destroy', $record) }}" onsubmit="return confirm('Delete this category?')">
                                        @csrf @method('DELETE')
                                        <x-ui.button type="submit" size="sm" variant="danger">Delete</x-ui.button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-8"><x-ui.empty-state title="No categories found">Create your first course category.</x-ui.empty-state></td></tr>
                @endforelse
            </tbody>
        </x-ui.table>
        <div class="mt-4">{{ $records->links() }}</div>
    </x-ui.card>
</x-layouts.admin>
