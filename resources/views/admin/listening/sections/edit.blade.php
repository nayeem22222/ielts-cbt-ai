<x-layouts.admin title="Edit Section" heading="Edit Section" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Listening Tests', 'href' => route($routePrefix.'.index')], ['label' => $listeningTest->title, 'href' => route($routePrefix.'.show', $listeningTest)], ['label' => 'Sections', 'href' => route($sectionsRoutePrefix.'.index', $listeningTest)], ['label' => 'Edit']]">
    @include('admin.listening.sections.partials.alerts')

    <div class="space-y-6">
        <x-ui.card>
            <form method="POST" action="{{ route($sectionsRoutePrefix.'.update', [$listeningTest, $section]) }}">
                @csrf @method('PUT')
                @include('admin.listening.sections.partials.form', ['submitLabel' => 'Update Section'])
            </form>
        </x-ui.card>
        @include('admin.listening.sections.partials.readiness-card')
    </div>
</x-layouts.admin>
