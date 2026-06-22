<x-layouts.admin title="Trash" :heading="'Deleted '.$entityLabel" eyebrow="Course Management" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => $entityLabel, 'href' => $indexRoute], ['label' => 'Trash']]">
    <x-ui.card title="Deleted Records">
        <x-admin.crud-toolbar :route-prefix="$routePrefix" :filters="$filters" :sort="$sort" :direction="$direction" :definition="$definition" :trashed="true" />
        <x-ui.table>
            <thead>
                <tr class="text-left text-xs uppercase aa-muted">
                    <th class="p-4"><input type="checkbox" data-crud-select-all></th>
                    @foreach ($columns as $label)
                        <th class="p-4">{{ $label }}</th>
                    @endforeach
                    <th class="p-4">Deleted</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                @forelse ($records as $record)
                    <tr>
                        <td class="p-4"><input type="checkbox" data-crud-row-checkbox value="{{ $record->id }}"></td>
                        @foreach (array_keys($columns) as $field)
                            <td class="p-4">{{ data_get($record, $field) }}</td>
                        @endforeach
                        <td class="p-4 text-sm aa-muted">{{ $record->deleted_at?->diffForHumans() }}</td>
                        <td class="p-4">
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route($routePrefix.'.restore', $record) }}">@csrf @method('PUT')<x-ui.button type="submit" size="sm" variant="secondary">Restore</x-ui.button></form>
                                <form method="POST" action="{{ route($routePrefix.'.force-destroy', $record) }}" onsubmit="return confirm('Permanently delete?')">@csrf @method('DELETE')<x-ui.button type="submit" size="sm" variant="danger">Delete forever</x-ui.button></form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($columns) + 3 }}" class="p-8"><x-ui.empty-state title="Trash is empty">No deleted records.</x-ui.empty-state></td></tr>
                @endforelse
            </tbody>
        </x-ui.table>
        <div class="mt-4">{{ $records->links() }}</div>
    </x-ui.card>
</x-layouts.admin>
