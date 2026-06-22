<x-layouts.admin title="Create Course" heading="Create Course" eyebrow="Course Management" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Courses', 'href' => route('admin.courses.index')], ['label' => 'Create']]">
    <x-ui.card><form method="POST" action="{{ route('admin.courses.store') }}">@csrf @include('pages.admin.courses._form', compact('statuses', 'examTypes', 'levels', 'categories') + ['submitLabel' => 'Create Course'])</form></x-ui.card>
</x-layouts.admin>
