<x-layouts.admin>
    <div class="mb-6">
        <h2 class="text-xl font-bold">Permissions</h2>
        <p class="text-sm aa-muted">System permission registry grouped by feature area.</p>
    </div>

    <x-ui.card title="Permission Registry">
        @foreach ($permissions as $group => $groupPermissions)
            <div class="@if (! $loop->first) mt-6 @endif">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-neutral-500">{{ $group }}</h3>
                <div class="grid gap-3 md:grid-cols-2">
                    @foreach ($groupPermissions as $permission)
                        <div class="rounded-xl border border-neutral-200 px-4 py-3">
                            <div class="font-medium">{{ $permission->description }}</div>
                            <div class="text-xs aa-muted">{{ $permission->name }}</div>
                            <div class="mt-2 text-xs">Assigned to {{ $permission->roles_count }} role(s)</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </x-ui.card>
</x-layouts.admin>
