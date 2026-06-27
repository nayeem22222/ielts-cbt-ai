<x-layouts.admin title="Create Listening Test" heading="Create Listening Test" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Listening Tests', 'href' => route($routePrefix.'.index')], ['label' => 'Create']]">
    <x-ui.card>
        <form method="POST" action="{{ route($routePrefix.'.store') }}">
            @csrf
            @include('admin.listening.tests.partials.form', ['submitLabel' => 'Create Listening Test'])
        </form>
    </x-ui.card>
</x-layouts.admin>
