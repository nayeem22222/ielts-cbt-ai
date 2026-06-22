@props([
    'routePrefix',
    'filters' => [],
    'sort' => null,
    'direction' => 'desc',
    'definition' => null,
    'roles' => [],
    'statuses' => [],
    'showRoleFilter' => false,
    'showStatusFilter' => false,
    'trashed' => false,
])

@php
    $indexRoute = $trashed ? $routePrefix.'.trash' : $routePrefix.'.index';
    $toggleSort = fn (string $column) => ($sort === $column && $direction === 'asc') ? 'desc' : 'asc';
@endphp

<div class="mb-4 space-y-4" data-crud-toolbar>
    <form method="GET" action="{{ route($indexRoute) }}" class="grid gap-3 lg:grid-cols-[1.2fr_repeat(3,minmax(0,.7fr))_auto]">
        <x-ui.input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search..."/>

        @if ($showRoleFilter)
            <x-ui.select name="role" label="Role">
                <option value="">All roles</option>
                @foreach ($roles as $roleOption)
                    <option value="{{ $roleOption->value }}" @selected(($filters['role'] ?? '') === $roleOption->value)>{{ $roleOption->label() }}</option>
                @endforeach
            </x-ui.select>
        @endif

        @if ($showStatusFilter)
            <x-ui.select name="status" label="Status">
                <option value="">All statuses</option>
                @foreach ($statuses as $statusOption)
                    <option value="{{ $statusOption->value }}" @selected(($filters['status'] ?? '') === $statusOption->value)>{{ $statusOption->label() }}</option>
                @endforeach
            </x-ui.select>
        @endif

        @if ($definition)
            <x-ui.select name="sort" label="Sort by">
                @foreach ($definition->sortable as $column)
                    <option value="{{ $column }}" @selected($sort === $column)>{{ ucwords(str_replace('_', ' ', $column)) }}</option>
                @endforeach
            </x-ui.select>
        @endif

        <div class="flex items-end gap-2">
            <x-ui.select name="direction" label="Direction">
                <option value="desc" @selected($direction === 'desc')>Descending</option>
                <option value="asc" @selected($direction === 'asc')>Ascending</option>
            </x-ui.select>
            <x-ui.button type="submit">Apply</x-ui.button>
            <x-ui.button href="{{ route($indexRoute) }}" variant="outline">Reset</x-ui.button>
        </div>
    </form>

    <div class="flex flex-wrap items-center gap-2">
        @unless ($trashed)
            <x-ui.button href="{{ route($routePrefix.'.export', request()->query()) }}" variant="outline">Export CSV</x-ui.button>
            <x-ui.button href="{{ route($routePrefix.'.import.form') }}" variant="outline">Import Excel</x-ui.button>
            <x-ui.button href="{{ route($routePrefix.'.trash') }}" variant="outline">Trash</x-ui.button>
        @else
            <x-ui.button href="{{ route($routePrefix.'.index') }}" variant="outline">Back to list</x-ui.button>
        @endunless
    </div>

    <form method="POST" action="{{ route($routePrefix.'.bulk') }}" class="flex flex-wrap items-center gap-2" data-crud-bulk-form>
        @csrf
        <x-ui.select name="action" label="Bulk action">
            @if ($trashed)
                <option value="restore">Restore selected</option>
                <option value="force_delete">Permanently delete</option>
            @else
                <option value="delete">Delete selected</option>
            @endif
        </x-ui.select>
        <div id="crud-bulk-ids"></div>
        <x-ui.button type="submit" variant="danger" onclick="return confirm('Apply bulk action to selected records?')">Run bulk action</x-ui.button>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const bulkForm = document.querySelector('[data-crud-bulk-form]');
        const holder = document.getElementById('crud-bulk-ids');
        const master = document.querySelector('[data-crud-select-all]');

        const syncBulkIds = () => {
            if (!bulkForm || !holder) return;
            holder.innerHTML = '';
            document.querySelectorAll('[data-crud-row-checkbox]:checked').forEach((input) => {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'ids[]';
                hidden.value = input.value;
                holder.appendChild(hidden);
            });
        };

        document.querySelectorAll('[data-crud-row-checkbox]').forEach((input) => {
            input.addEventListener('change', syncBulkIds);
        });

        master?.addEventListener('change', () => {
            document.querySelectorAll('[data-crud-row-checkbox]').forEach((input) => {
                input.checked = master.checked;
            });
            syncBulkIds();
        });

        bulkForm?.addEventListener('submit', syncBulkIds);
    });
</script>
