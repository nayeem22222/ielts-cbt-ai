<x-layouts.admin
    title="Import Users"
    heading="Import Users"
    eyebrow="Access Management"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard')],
        ['label' => 'Users', 'href' => route('admin.users.index')],
        ['label' => 'Import'],
    ]"
>
    <x-ui.card title="Import spreadsheet" subtitle="Upload CSV or Excel (.xlsx) with a header row">
        <p class="mb-4 text-sm aa-muted">
            Expected columns:
            <strong>{{ implode(', ', $importColumns) }}</strong>
        </p>

        <form method="POST" action="{{ route($routePrefix.'.import') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="file" name="file" accept=".csv,.xlsx" class="block w-full rounded-2xl border border-neutral-200 px-4 py-3 dark:border-neutral-800" required>
            @error('file')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            <div class="flex gap-3">
                <x-ui.button type="submit">Import</x-ui.button>
                <x-ui.button href="{{ route($routePrefix.'.index') }}" variant="outline">Cancel</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-layouts.admin>
