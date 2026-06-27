<x-layouts.admin title="Create Transcript" heading="Create Transcript" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Transcripts', 'href' => route($routePrefix.'.index')], ['label' => 'Create']]">
    @include('admin.listening.sections.partials.alerts')

    @if (! empty($returnUrl))
        <x-ui.alert tone="blue" class="mb-4" title="Creating for a listening section">
            After saving, you will return to the section page to attach this transcript in one click.
        </x-ui.alert>
    @endif

    <x-ui.card>
        <form method="POST" action="{{ route($routePrefix.'.store') }}">
            @csrf
            @if (! empty($returnUrl))
                <input type="hidden" name="return_url" value="{{ $returnUrl }}">
            @endif
            @include('admin.listening.transcripts.partials.form', [
                'submitLabel' => 'Create Transcript',
                'cancelUrl' => $returnUrl ?? route($routePrefix.'.index'),
            ])
        </form>
    </x-ui.card>
</x-layouts.admin>
