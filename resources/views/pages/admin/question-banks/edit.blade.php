<x-layouts.admin title="Edit Question Bank" heading="Edit Question Bank" eyebrow="Test Builder" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Question Banks', 'href' => route('admin.question-banks.index')], ['label' => 'Edit']]">
    <x-ui.card>
        <form method="POST" action="{{ route('admin.question-banks.update', $questionBank) }}">
            @csrf @method('PUT')
            @include('pages.admin.question-banks._form', array_merge(compact('statuses', 'examTypes', 'questionBank'), ['submitLabel' => 'Update Question Bank']))
        </form>
    </x-ui.card>
</x-layouts.admin>
