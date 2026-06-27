<x-layouts.admin title="Edit Listening Test" heading="Edit Listening Test" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Listening Tests', 'href' => route($routePrefix.'.index')], ['label' => $listeningTest->title], ['label' => 'Edit']]">
    <div class="space-y-6">
        <div class="flex justify-end">
            @can('viewAny', [App\Models\Listening\ListeningSection::class, $listeningTest])
                <x-ui.button href="{{ route('admin.listening.tests.sections.index', $listeningTest) }}" variant="outline">Manage Sections</x-ui.button>
            @endcan
        </div>
        <x-ui.card>
            <form method="POST" action="{{ route($routePrefix.'.update', $listeningTest) }}">
                @csrf @method('PUT')
                @include('admin.listening.tests.partials.form', ['submitLabel' => 'Update Listening Test'])
            </form>
        </x-ui.card>

        @include('admin.listening.tests.partials.readiness-card')
        @include('admin.listening.tests.partials.settings-form')
    </div>
</x-layouts.admin>
