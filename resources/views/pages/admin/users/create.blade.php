<x-layouts.admin
    title="Create User"
    heading="Create User"
    eyebrow="Access Management"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard')],
        ['label' => 'Users', 'href' => route('admin.users.index')],
        ['label' => 'Create'],
    ]"
>
    <div class="mb-6">
        <h2 class="text-xl font-bold">Add User</h2>
        <p class="text-sm aa-muted">Create teacher, admin, or student accounts manually.</p>
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
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            @include('pages.admin.users._form', [
                'roles' => $roles,
                'statuses' => $statuses,
                'submitLabel' => 'Create User',
            ])
        </form>
    </x-ui.card>
</x-layouts.admin>
