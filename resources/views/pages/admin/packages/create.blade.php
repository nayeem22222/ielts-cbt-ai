<x-layouts.admin title="Create Package" heading="Create Package" eyebrow="Commerce" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Packages', 'href' => route('admin.packages.index')], ['label' => 'Create']]">
    <x-ui.card>
        <form method="POST" action="{{ route('admin.packages.store') }}">
            @csrf
            @include('pages.admin.packages._form', array_merge(compact('modules', 'statuses', 'intervals', 'discountTypes', 'courses'), ['submitLabel' => 'Create Package']))
        </form>
    </x-ui.card>
</x-layouts.admin>
