<x-layouts.admin title="Listening Tests" heading="Listening Tests" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Listening'], ['label' => 'Tests']]">
    <div class="mb-6 flex justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-neutral-900 dark:text-white">Listening Tests</h2>
            <p class="text-sm aa-muted">Create and manage IELTS Listening tests.</p>
        </div>
        @can('create', \App\Models\Listening\ListeningTest::class)
            <x-ui.button href="{{ route($routePrefix.'.create') }}">Add Listening Test</x-ui.button>
        @endcan
    </div>

    <x-ui.card title="Listening Test Directory">
        @include('admin.listening.tests.partials._table')
    </x-ui.card>
</x-layouts.admin>
