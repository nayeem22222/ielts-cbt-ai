<x-layouts.admin title="Edit Course" heading="Edit Course" eyebrow="Course Management" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Courses', 'href' => route('admin.courses.index')], ['label' => $course->title]]">
    <x-ui.card><form method="POST" action="{{ route('admin.courses.update', $course) }}">@csrf @method('PUT') @include('pages.admin.courses._form', compact('course', 'statuses', 'examTypes', 'levels', 'categories') + ['submitLabel' => 'Update Course'])</form></x-ui.card>
</x-layouts.admin>
