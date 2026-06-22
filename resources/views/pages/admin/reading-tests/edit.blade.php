<x-layouts.admin title="Edit Reading Test" heading="Edit Reading Test" eyebrow="Test Builder" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Reading Tests', 'href' => route('admin.reading-tests.index')], ['label' => 'Edit']]">
    <x-ui.card>
        <form method="POST" action="{{ route('admin.reading-tests.update', $readingTest) }}">
            @csrf @method('PUT')
            @include('pages.admin.reading-tests._form', array_merge(compact('statuses', 'examTypes', 'readingTest'), ['submitLabel' => 'Update Reading Test']))
        </form>
    </x-ui.card>
</x-layouts.admin>
