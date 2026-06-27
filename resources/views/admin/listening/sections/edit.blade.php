<x-layouts.admin title="Edit Section" heading="Edit Section" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Listening Tests', 'href' => route($routePrefix.'.index')], ['label' => $listeningTest->title, 'href' => route($routePrefix.'.show', $listeningTest)], ['label' => 'Sections', 'href' => route($sectionsRoutePrefix.'.index', $listeningTest)], ['label' => 'Edit']]">
    @include('admin.listening.sections.partials.alerts')

    <div class="space-y-6">
        <x-ui.card title="Section Settings">
            <p class="mb-4 text-sm aa-muted">Update section details here. Manage transcript attachment in the panel below.</p>
            <form method="POST" action="{{ route($sectionsRoutePrefix.'.update', [$listeningTest, $section]) }}">
                @csrf @method('PUT')
                @include('admin.listening.sections.partials.form', ['submitLabel' => 'Update Section', 'hideTranscriptSelector' => true])
            </form>
        </x-ui.card>

        <div class="grid gap-6 lg:grid-cols-2">
            @include('admin.listening.sections.partials.readiness-card')
            @include('admin.listening.sections.partials.transcript-card')
        </div>
    </div>
</x-layouts.admin>
