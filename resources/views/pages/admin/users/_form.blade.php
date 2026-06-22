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

<div class="mt-4 rounded-2xl border border-neutral-200 bg-white/70 p-4 dark:border-neutral-800 dark:bg-neutral-900/70">
    <label class="flex items-start gap-3">
        <input
            type="checkbox"
            name="email_verified"
            value="1"
            class="mt-1 rounded border-neutral-300 text-brand-600 focus:ring-brand-500"
            @checked(old('email_verified', $user ? $user->hasVerifiedEmail() : true))
        >
        <span>
            <span class="block font-semibold">Email verified</span>
            <span class="block text-sm aa-muted">Admin can manually verify or unverify this account without changing the existing email verification flow.</span>
        </span>
    </label>
</div>

<div class="mt-4 grid gap-4 md:grid-cols-2">
    <x-ui.input name="password" type="password" label="{{ $user ? 'New password' : 'Password' }}" :required="! $user" placeholder="{{ $user ? 'Leave blank to keep current password' : 'Minimum 8 characters' }}" />
    <x-ui.input name="password_confirmation" type="password" label="Confirm password" :required="! $user" />
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <x-ui.button type="submit">{{ $submitLabel }}</x-ui.button>
    <x-ui.button href="{{ route('admin.users.index') }}" variant="outline">Cancel</x-ui.button>
</div>
