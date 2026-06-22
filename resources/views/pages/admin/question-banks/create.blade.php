<x-layouts.admin title="Create Question Bank" heading="Create Question Bank" eyebrow="Test Builder" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Question Banks', 'href' => route('admin.question-banks.index')], ['label' => 'Create']]">
    <x-ui.card>
        <form method="POST" action="{{ route('admin.question-banks.store') }}">
            @csrf
            @include('pages.admin.question-banks._form', array_merge(compact('statuses', 'examTypes'), ['submitLabel' => 'Create Question Bank']))
        </form>
    </x-ui.card>
</x-layouts.admin>
