<x-layouts.admin title="Create Category" heading="Create Category" eyebrow="Course Management" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Categories', 'href' => route('admin.course-categories.index')], ['label' => 'Create']]">
    <x-ui.card>
        <form method="POST" action="{{ route('admin.course-categories.store') }}">
            @csrf
            @include('pages.admin.course-categories._form', ['statuses' => $statuses, 'parents' => $parents, 'submitLabel' => 'Create Category'])
        </form>
    </x-ui.card>
</x-layouts.admin>
