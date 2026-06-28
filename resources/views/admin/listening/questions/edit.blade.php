<x-layouts.admin title="Edit Question" heading="Edit Question" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Listening Tests', 'href' => route($routePrefix.'.index')], ['label' => $listeningTest->title, 'href' => route($routePrefix.'.show', $listeningTest)], ['label' => 'Questions', 'href' => route($questionsRoutePrefix.'.index', [$listeningTest, $section, $group])], ['label' => 'Edit Q'.$question->question_number]]">
    @include('admin.listening.sections.partials.alerts')

    @include('admin.listening.question-builder.partials.context-nav')

    <x-ui.card><form method="POST" action="{{ route($questionsRoutePrefix.'.update', [$listeningTest, $section, $group, $question]) }}">@csrf @method('PUT') @include('admin.listening.questions.partials.form', ['submitLabel' => 'Update Question'])</form></x-ui.card>
</x-layouts.admin>
