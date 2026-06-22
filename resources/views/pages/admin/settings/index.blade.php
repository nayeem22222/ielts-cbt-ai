@php
    $groupValues = $settings[$activeTab->value] ?? [];
    $canUpdate = auth()->user()?->can('settings.update') ?? false;
@endphp

<x-layouts.admin
    title="Settings"
    heading="Platform Settings"
    eyebrow="Platform"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard')],
        ['label' => 'Settings'],
    ]"
>
    <div class="mb-6">
        <h2 class="text-xl font-bold">Settings</h2>
        <p class="text-sm aa-muted">Configure general, brand, AI, payment, infrastructure, security, and backup preferences.</p>
    </div>

    @if (session('status'))
        <x-ui.alert tone="green" :title="session('status')" class="mb-4" />
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

    <div class="mb-6 overflow-x-auto">
        <x-ui.tabs>
            @foreach ($tabs as $tab)
                <a
                    href="{{ route('admin.settings.index', ['tab' => $tab->value]) }}"
                    class="rounded-xl px-4 py-2 text-sm font-medium transition {{ $activeTab === $tab ? 'bg-white text-brand-600 shadow dark:bg-neutral-800 dark:text-brand-300' : 'text-neutral-600 hover:bg-white/70 dark:text-neutral-300 dark:hover:bg-neutral-800/70' }}"
                >
                    <span aria-hidden="true">{{ $tab->icon() }}</span>
                    {{ $tab->label() }}
                </a>
            @endforeach
        </x-ui.tabs>
    </div>

    @if (in_array($activeTab->value, ['storage', 'redis', 'queue'], true))
        @include('pages.admin.settings._infrastructure', [
            'activeTab' => $activeTab,
            'infrastructure' => $infrastructure,
        ])
    @endif

    <x-ui.card :title="$activeTab->label().' Settings'">
        <form method="POST" action="{{ route('admin.settings.update', $activeTab->value) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @include('pages.admin.settings.groups.'.$activeTab->value, [
                'values' => $groupValues,
                'canUpdate' => $canUpdate,
            ])

            @can('settings.update')
                <div class="flex flex-wrap gap-3 border-t border-neutral-200 pt-4 dark:border-neutral-800">
                    <x-ui.button type="submit">Save {{ $activeTab->label() }} Settings</x-ui.button>
                </div>
            @endcan
        </form>
    </x-ui.card>

    @if ($activeTab === \App\Enums\Settings\SettingsGroup::Backup)
        @can('settings.update')
            <x-ui.card title="Run Backup Now" subtitle="Create an on-demand backup manifest and settings export" class="mt-6">
                <form method="POST" action="{{ route('admin.settings.backup.run') }}">
                    @csrf
                    <x-ui.button type="submit" variant="secondary">Run Backup</x-ui.button>
                </form>
            </x-ui.card>
        @endcan
    @endif
</x-layouts.admin>
