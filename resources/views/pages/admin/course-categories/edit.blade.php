<x-layouts.admin title="Edit Category" heading="Edit Category" eyebrow="Course Management" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Categories', 'href' => route('admin.course-categories.index')], ['label' => $category->name]]">
    <x-ui.card>
        <form method="POST" action="{{ route('admin.course-categories.update', $category) }}">
            @csrf @method('PUT')
            @include('pages.admin.course-categories._form', ['category' => $category, 'statuses' => $statuses, 'parents' => $parents, 'submitLabel' => 'Update Category'])
        </form>
    </x-ui.card>
</x-layouts.admin>
