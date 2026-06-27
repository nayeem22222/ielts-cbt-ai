<x-layouts.admin title="Create Section" heading="Create Section" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Listening Tests', 'href' => route($routePrefix.'.index')], ['label' => $listeningTest->title, 'href' => route($routePrefix.'.show', $listeningTest)], ['label' => 'Sections', 'href' => route($sectionsRoutePrefix.'.index', $listeningTest)], ['label' => 'Create']]">
    @include('admin.listening.sections.partials.alerts')

    @if (empty($availableSectionNumbers))
        <x-ui.card>
            <x-ui.empty-state title="All 4 sections already exist">
                All 4 sections already exist. Please edit an existing section.
            </x-ui.empty-state>
            <div class="mt-4">
                <x-ui.button href="{{ route($sectionsRoutePrefix.'.index', $listeningTest) }}">Back to Sections</x-ui.button>
            </div>
        </x-ui.card>
    @else
        <x-ui.card>
            <form method="POST" action="{{ route($sectionsRoutePrefix.'.store', $listeningTest) }}">
                @csrf
                @include('admin.listening.sections.partials.form', ['submitLabel' => 'Create Section'])
            </form>
        </x-ui.card>
    @endif
</x-layouts.admin>
