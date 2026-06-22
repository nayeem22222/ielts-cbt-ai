<x-layouts.admin title="Edit Package" heading="Edit Package" eyebrow="Commerce" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Packages', 'href' => route('admin.packages.index')], ['label' => $package->name]]">
    <x-ui.card>
        <form method="POST" action="{{ route('admin.packages.update', $package) }}">
            @csrf @method('PUT')
            @include('pages.admin.packages._form', array_merge(compact('package', 'modules', 'statuses', 'intervals', 'discountTypes', 'courses', 'selectedCourses'), ['submitLabel' => 'Update Package']))
        </form>
    </x-ui.card>
</x-layouts.admin>
