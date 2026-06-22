<x-layouts.admin
    title="Roles"
    heading="Roles"
    eyebrow="Access Management"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard')],
        ['label' => 'Roles'],
    ]"
>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold">Roles</h2>
            <p class="text-sm aa-muted">Review roles and manage permission assignments.</p>
        </div>
        <x-ui.button href="{{ route('admin.permissions.index') }}" variant="secondary">View Permissions</x-ui.button>
    </div>

    @if (session('status'))
        <x-ui.alert tone="green" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    <x-ui.card title="Role Directory" subtitle="Each role inherits permissions used by middleware and policies">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-neutral-200 text-left">
                        <th class="px-3 py-2">Role</th>
                        <th class="px-3 py-2">Users</th>
                        <th class="px-3 py-2">Permissions</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr class="border-b border-neutral-100">
                            <td class="px-3 py-3">
                                <div class="font-semibold">{{ $role->label }}</div>
                                <div class="text-xs aa-muted">{{ $role->slug }}</div>
                            </td>
                            <td class="px-3 py-3">{{ $role->users_count }}</td>
                            <td class="px-3 py-3">{{ $role->permissions_count }}</td>
                            <td class="px-3 py-3 text-right">
                                @can('updatePermissions', $role)
                                    <x-ui.button href="{{ route('admin.roles.edit', $role) }}" variant="secondary">Manage</x-ui.button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>
</x-layouts.admin>
