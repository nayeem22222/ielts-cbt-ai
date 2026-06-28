<x-layouts.admin title="Edit Question Group" heading="Edit Question Group" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Listening Tests', 'href' => route($routePrefix.'.index')], ['label' => $listeningTest->title, 'href' => route($routePrefix.'.show', $listeningTest)], ['label' => $group->title ?: 'Group', 'href' => route($groupsRoutePrefix.'.show', [$listeningTest, $section, $group])], ['label' => 'Edit']]">
    @include('admin.listening.sections.partials.alerts')

    @include('admin.listening.question-builder.partials.context-nav')

    <x-ui.card>
        <form method="POST" action="{{ route($groupsRoutePrefix.'.update', [$listeningTest, $section, $group]) }}">
            @csrf @method('PUT')
            @include('admin.listening.question-groups.partials.form', ['submitLabel' => 'Update Question Group'])
        </form>
    </x-ui.card>
</x-layouts.admin>
