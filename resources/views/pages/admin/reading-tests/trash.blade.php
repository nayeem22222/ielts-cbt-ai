<x-layouts.admin title="Deleted Reading Tests" heading="Deleted Reading Tests" eyebrow="Test Builder" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Reading Tests', 'href' => route('admin.reading-tests.index')], ['label' => 'Trash']]">
    <x-ui.card title="Trash">
        <x-admin.crud-toolbar :route-prefix="$routePrefix" :filters="$filters" :sort="$sort" :direction="$direction" :definition="$definition" :trashed="true" />

        <x-ui.table>
            <thead>
                <tr class="text-left text-xs uppercase aa-muted">
                    <th class="p-4"><input type="checkbox" data-crud-select-all></th>
                    <th class="p-4">ID</th>
                    <th class="p-4">Title</th>
                    <th class="p-4">Slug</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Deleted</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                @forelse ($records as $record)
                    <tr>
                        <td class="p-4"><input type="checkbox" data-crud-row-checkbox value="{{ $record->id }}"></td>
                        <td class="p-4">{{ $record->id }}</td>
                        <td class="p-4 font-medium">{{ $record->title }}</td>
                        <td class="p-4 text-xs aa-muted">{{ $record->slug }}</td>
                        <td class="p-4">{{ $record->status?->label() ?? 'Draft' }}</td>
                        <td class="p-4 text-sm aa-muted">{{ $record->deleted_at?->diffForHumans() }}</td>
                        <td class="p-4">
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('admin.reading-tests.restore', $record->id) }}">
                                    @csrf
                                    <x-ui.button type="submit" size="sm" variant="secondary">Restore</x-ui.button>
                                </form>
                                <form method="POST" action="{{ route('admin.reading-tests.force-delete', $record->id) }}" onsubmit="return confirm('Permanently delete this reading test?')">
                                    @csrf @method('DELETE')
                                    <x-ui.button type="submit" size="sm" variant="danger">Force Delete</x-ui.button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-8"><x-ui.empty-state title="Trash is empty">No deleted reading tests.</x-ui.empty-state></td></tr>
                @endforelse
            </tbody>
        </x-ui.table>

        <div class="mt-4">{{ $records->links() }}</div>
    </x-ui.card>
</x-layouts.admin>
