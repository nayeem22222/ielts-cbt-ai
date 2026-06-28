@php
    $breadcrumbGroup = $group->title ?: 'Group';
@endphp
<x-layouts.admin title="Questions" heading="Questions" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Listening Tests', 'href' => route($routePrefix.'.index')], ['label' => $listeningTest->title, 'href' => route($routePrefix.'.show', $listeningTest)], ['label' => 'Question Builder', 'href' => route($builderRoutePrefix.'.index', $listeningTest)], ['label' => 'Section '.$section->section_number, 'href' => route($sectionsRoutePrefix.'.builder.index', [$listeningTest, $section])], ['label' => $breadcrumbGroup, 'href' => route($groupsRoutePrefix.'.show', [$listeningTest, $section, $group])], ['label' => 'Questions']]">
    @include('admin.listening.sections.partials.alerts')

    @include('admin.listening.question-builder.partials.context-nav')

    @include('admin.listening.question-builder.partials.workflow-steps', ['current' => 'questions'])

    <div class="mb-6 flex flex-wrap gap-2">
        @can('create', [\App\Models\Listening\ListeningQuestion::class, $group])
            <x-ui.button href="{{ route($questionsRoutePrefix.'.create', [$listeningTest, $section, $group]) }}">Add Question</x-ui.button>
        @endcan
        @include('admin.listening.questions.partials.bulk-create-form', ['bulkGroup' => $group])
    </div>

    <div class="space-y-3">
        @forelse ($questions as $question)
            @include('admin.listening.questions.partials.question-card', ['question' => $question])
        @empty
            <x-ui.empty-state title="No questions yet">
                Use <strong>Bulk Create Questions</strong> to add Q{{ $group->start_question_number }}–Q{{ $group->end_question_number }} in one click, then edit each answer.
            </x-ui.empty-state>
        @endforelse
    </div>
</x-layouts.admin>
