<x-layouts.admin
    title="Edit User"
    heading="Edit User"
    eyebrow="Access Management"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard')],
        ['label' => 'Users', 'href' => route('admin.users.index')],
        ['label' => $user->name],
    ]"
>
    <div class="mb-6">
        <h2 class="text-xl font-bold">Edit User</h2>
        <p class="text-sm aa-muted">Update account details, role, status, or password.</p>
    </div>

    @if ($errors->any())
        <x-ui.alert tone="red" title="Please fix the following" class="mb-4">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    <x-ui.card>
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PUT')
            @include('pages.admin.users._form', [
                'user' => $user,
                'roles' => $roles,
                'statuses' => $statuses,
                'submitLabel' => 'Update User',
            ])
        </form>
    </x-ui.card>

    @can('assignPermissions', $user)
        <x-ui.card title="Direct Permissions" subtitle="Grant extra permissions beyond the user's role" class="mt-6">
            <form method="POST" action="{{ route('admin.users.permissions.sync', $user) }}" class="space-y-6">
                @csrf
                @method('PUT')

                @foreach ($permissions as $group => $groupPermissions)
                    <div>
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-neutral-500">{{ $group }}</h3>
                        <div class="grid gap-2 md:grid-cols-2">
                            @foreach ($groupPermissions as $permission)
                                <label class="flex items-start gap-2 rounded-xl border border-neutral-200 px-3 py-2">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked(in_array($permission->name, $directPermissions, true))>
                                    <span>
                                        <span class="block font-medium">{{ $permission->description }}</span>
                                        <span class="block text-xs aa-muted">{{ $permission->name }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <x-ui.button type="submit">Save Direct Permissions</x-ui.button>
            </form>
        </x-ui.card>
    @endcan
</x-layouts.admin>
