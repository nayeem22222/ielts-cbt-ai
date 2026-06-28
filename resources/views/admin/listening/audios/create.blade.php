<x-layouts.admin title="Upload Listening Audio" heading="Upload Listening Audio" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Listening Audio', 'href' => route($routePrefix.'.index')], ['label' => 'Upload']]">
    @include('admin.listening.sections.partials.alerts')

    <x-ui.card title="Upload Audio">
        @include('admin.listening.audios.partials.upload-form')
    </x-ui.card>
</x-layouts.admin>
