<x-layouts.admin
    title="Trash"
    heading="Deleted Users"
    eyebrow="Access Management"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard')],
        ['label' => 'Users', 'href' => route('admin.users.index')],
        ['label' => 'Trash'],
    ]"
>
    <x-ui.card title="Deleted Records" subtitle="Restore or permanently remove soft-deleted users">
        <x-admin.crud-toolbar
            :route-prefix="$routePrefix"
            :filters="$filters"
            :sort="$sort"
            :direction="$direction"
            :definition="$definition"
            :trashed="true"
        />

        <x-ui.table>
            <thead>
                <tr class="text-left text-xs uppercase aa-muted">
                    <th class="p-4"><input type="checkbox" data-crud-select-all></th>
                    <th class="p-4">Name</th>
                    <th class="p-4">Email</th>
                    <th class="p-4">Deleted</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                @forelse ($records as $record)
                    <tr>
                        <td class="p-4"><input type="checkbox" data-crud-row-checkbox value="{{ $record->id }}"></td>
                        <td class="p-4 font-medium">{{ $record->name }}</td>
                        <td class="p-4">{{ $record->email }}</td>
                        <td class="p-4 text-sm aa-muted">{{ $record->deleted_at?->diffForHumans() }}</td>
                        <td class="p-4">
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route($routePrefix.'.restore', $record) }}">
                                    @csrf
                                    @method('PUT')
                                    <x-ui.button type="submit" size="sm" variant="secondary">Restore</x-ui.button>
                                </form>
                                <form method="POST" action="{{ route($routePrefix.'.force-destroy', $record) }}" onsubmit="return confirm('Permanently delete this record?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button type="submit" size="sm" variant="danger">Delete forever</x-ui.button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8">
                            <x-ui.empty-state title="Trash is empty">No deleted records found.</x-ui.empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>

        <div class="mt-4">{{ $records->links() }}</div>
    </x-ui.card>
</x-layouts.admin>
