<x-layouts.admin>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold">Users</h2>
            <p class="text-sm aa-muted">Manage student, teacher, and admin accounts.</p>
        </div>
        <x-ui.button href="{{ route('admin.users.create') }}">Add User</x-ui.button>
    </div>

    @if (session('status'))
        <x-ui.alert tone="green" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    <x-ui.card title="User Directory" subtitle="Search, filter, and manage platform accounts">
        <form method="GET" action="{{ route('admin.users.index') }}" class="mb-4 grid gap-3 md:grid-cols-[1.4fr_.8fr_.8fr_auto]">
            <x-ui.input name="search" value="{{ $filters['search'] }}" placeholder="Search name, email, or phone"/>
            <x-ui.select name="role" label="Role">
                <option value="">All roles</option>
                @foreach ($roles as $roleOption)
                    <option value="{{ $roleOption->value }}" @selected($filters['role'] === $roleOption->value)>{{ $roleOption->label() }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select name="status" label="Status">
                <option value="">All statuses</option>
                @foreach ($statuses as $statusOption)
                    <option value="{{ $statusOption->value }}" @selected($filters['status'] === $statusOption->value)>{{ $statusOption->label() }}</option>
                @endforeach
            </x-ui.select>
            <div class="flex items-end gap-2">
                <x-ui.button type="submit">Filter</x-ui.button>
                <x-ui.button href="{{ route('admin.users.index') }}" variant="outline">Reset</x-ui.button>
            </div>
        </form>

        <x-ui.table>
            <thead>
                <tr class="text-left text-xs uppercase aa-muted">
                    <th class="p-4">Name</th>
                    <th class="p-4">Email</th>
                    <th class="p-4">Phone</th>
                    <th class="p-4">Role</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Last Login</th>
                    <th class="p-4">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                @forelse ($users as $user)
                    @php
                        $role = $user->primaryRole();
                        $statusTone = $user->status === 'active' ? 'green' : 'red';
                    @endphp
                    <tr>
                        <td class="p-4 font-medium">{{ $user->name }}</td>
                        <td class="p-4">{{ $user->email }}</td>
                        <td class="p-4">{{ $user->phone ?: '—' }}</td>
                        <td class="p-4">
                            <x-ui.badge tone="blue">{{ $role?->label() ?? 'Unknown' }}</x-ui.badge>
                        </td>
                        <td class="p-4">
                            <x-ui.badge :tone="$statusTone">{{ ucfirst($user->status) }}</x-ui.badge>
                        </td>
                        <td class="p-4 text-sm aa-muted">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                        <td class="p-4">
                            <div class="flex flex-wrap gap-2">
                                @can('update', $user)
                                    <x-ui.button href="{{ route('admin.users.edit', $user) }}" size="sm" variant="outline">Edit</x-ui.button>
                                @endcan
                                @can('delete', $user)
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user? This action can be restored from soft delete.')">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button type="submit" size="sm" variant="danger">Delete</x-ui.button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-8">
                            <x-ui.empty-state title="No users found">Try adjusting your search or filters.</x-ui.empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </x-ui.card>
</x-layouts.admin>
