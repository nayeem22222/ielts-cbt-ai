<x-layouts.admin title="Create Question" heading="Create Question" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Listening Tests', 'href' => route($routePrefix.'.index')], ['label' => $listeningTest->title, 'href' => route($routePrefix.'.show', $listeningTest)], ['label' => 'Questions', 'href' => route($questionsRoutePrefix.'.index', [$listeningTest, $section, $group])], ['label' => 'Create']]">
    @include('admin.listening.sections.partials.alerts')

    @include('admin.listening.question-builder.partials.context-nav')

    <x-ui.card><form method="POST" action="{{ route($questionsRoutePrefix.'.store', [$listeningTest, $section, $group]) }}">@csrf @include('admin.listening.questions.partials.form', ['submitLabel' => 'Create Question'])</form></x-ui.card>
</x-layouts.admin>
