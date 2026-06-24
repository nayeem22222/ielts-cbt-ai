<x-layouts.admin title="Create Reading Test" heading="Create Reading Test" eyebrow="Test Builder" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Reading Tests', 'href' => route('admin.reading-tests.index')], ['label' => 'Create']]">
    <x-ui.card>
        <form method="POST" action="{{ route('admin.reading-tests.store') }}">
            @csrf
            @include('pages.admin.reading-tests._form', array_merge(compact('statuses', 'examTypes', 'readingTest'), ['submitLabel' => 'Create Reading Test']))
        </form>
    </x-ui.card>
</x-layouts.admin>
