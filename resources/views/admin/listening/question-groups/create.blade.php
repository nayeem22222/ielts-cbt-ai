<x-layouts.admin title="Create Question Group" heading="Create Question Group" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Listening Tests', 'href' => route($routePrefix.'.index')], ['label' => $listeningTest->title, 'href' => route($routePrefix.'.show', $listeningTest)], ['label' => 'Section '.$section->section_number, 'href' => route($sectionsRoutePrefix.'.builder.index', [$listeningTest, $section])], ['label' => 'Create Group']]">
    @include('admin.listening.sections.partials.alerts')

    @include('admin.listening.question-builder.partials.workflow-steps', ['current' => 'section'])

    <x-ui.card>
        <form method="POST" action="{{ route($groupsRoutePrefix.'.store', [$listeningTest, $section]) }}">
            @csrf
            @include('admin.listening.question-groups.partials.form', ['submitLabel' => 'Create Question Group'])
        </form>
    </x-ui.card>
</x-layouts.admin>
