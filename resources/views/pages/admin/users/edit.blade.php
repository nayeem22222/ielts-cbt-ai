<x-layouts.admin>
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
</x-layouts.admin>
