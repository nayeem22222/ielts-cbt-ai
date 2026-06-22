@props([
    'user' => null,
    'roles' => [],
    'statuses' => [],
    'submitLabel' => 'Save User',
])

<div class="grid gap-4 md:grid-cols-2">
    <x-ui.input name="name" label="Full name" value="{{ old('name', $user?->name) }}" required />
    <x-ui.input name="email" type="email" label="Email" value="{{ old('email', $user?->email) }}" required />
    <x-ui.input name="phone" label="Phone" value="{{ old('phone', $user?->phone) }}" placeholder="Optional" />
    <x-ui.select name="role" label="Role" required>
        @foreach ($roles as $roleOption)
            <option value="{{ $roleOption->value }}" @selected(old('role', $user?->roleSlug()) === $roleOption->value)>
                {{ $roleOption->label() }}
            </option>
        @endforeach
    </x-ui.select>
    <x-ui.select name="status" label="Status" required>
        @foreach ($statuses as $statusOption)
            <option value="{{ $statusOption->value }}" @selected(old('status', $user?->status ?? 'active') === $statusOption->value)>
                {{ $statusOption->label() }}
            </option>
        @endforeach
    </x-ui.select>
</div>

<div class="mt-4 grid gap-4 md:grid-cols-2">
    <x-ui.input name="password" type="password" label="{{ $user ? 'New password' : 'Password' }}" :required="! $user" placeholder="{{ $user ? 'Leave blank to keep current password' : 'Minimum 8 characters' }}" />
    <x-ui.input name="password_confirmation" type="password" label="Confirm password" :required="! $user" />
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <x-ui.button type="submit">{{ $submitLabel }}</x-ui.button>
    <x-ui.button href="{{ route('admin.users.index') }}" variant="outline">Cancel</x-ui.button>
</div>
