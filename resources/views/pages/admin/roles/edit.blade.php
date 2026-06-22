<x-layouts.admin
    title="Manage Role Permissions"
    heading="Manage Role Permissions"
    eyebrow="Access Management"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard')],
        ['label' => 'Roles', 'href' => route('admin.roles.index')],
        ['label' => $role->label],
    ]"
>
    <div class="mb-6">
        <h2 class="text-xl font-bold">Manage Role Permissions</h2>
        <p class="text-sm aa-muted">Assign capabilities to the {{ $role->label }} role.</p>
    </div>

    @if (session('status'))
        <x-ui.alert tone="green" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

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
        <form method="POST" action="{{ route('admin.roles.permissions.sync', $role) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @foreach ($permissions as $group => $groupPermissions)
                <div>
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-neutral-500">{{ $group }}</h3>
                    <div class="grid gap-2 md:grid-cols-2">
                        @foreach ($groupPermissions as $permission)
                            <label class="flex items-start gap-2 rounded-xl border border-neutral-200 px-3 py-2">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked(in_array($permission->name, $assigned, true))>
                                <span>
                                    <span class="block font-medium">{{ $permission->description }}</span>
                                    <span class="block text-xs aa-muted">{{ $permission->name }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="flex gap-3">
                <x-ui.button type="submit">Save Permissions</x-ui.button>
                <x-ui.button href="{{ route('admin.roles.index') }}" variant="secondary">Back</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-layouts.admin>
