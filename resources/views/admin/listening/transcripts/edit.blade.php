<x-layouts.admin :title="'Edit '.$transcript->title" :heading="'Edit Transcript'" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Transcripts', 'href' => route($routePrefix.'.index')], ['label' => $transcript->title ?: 'Transcript', 'href' => route($routePrefix.'.show', $transcript)], ['label' => 'Edit']]">
    @include('admin.listening.sections.partials.alerts')

    <x-ui.card>
        <form method="POST" action="{{ route($routePrefix.'.update', $transcript) }}">
            @csrf @method('PUT')
            @include('admin.listening.transcripts.partials.form', ['submitLabel' => 'Update Transcript'])
        </form>
    </x-ui.card>
</x-layouts.admin>
