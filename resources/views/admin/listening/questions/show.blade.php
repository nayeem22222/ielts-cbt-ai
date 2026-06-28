<x-layouts.admin :title="'Question '.$question->question_number" :heading="'Question '.$question->question_number" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Listening Tests', 'href' => route($routePrefix.'.index')], ['label' => $listeningTest->title, 'href' => route($routePrefix.'.show', $listeningTest)], ['label' => 'Question Builder', 'href' => route($builderRoutePrefix.'.index', $listeningTest)], ['label' => 'Section '.$section->section_number, 'href' => route($sectionsRoutePrefix.'.builder.index', [$listeningTest, $section])], ['label' => $group->title ?: 'Group', 'href' => route($groupsRoutePrefix.'.show', [$listeningTest, $section, $group])], ['label' => 'Questions', 'href' => route($questionsRoutePrefix.'.index', [$listeningTest, $section, $group])], ['label' => 'Q'.$question->question_number]]">
    @include('admin.listening.sections.partials.alerts')

    @include('admin.listening.question-builder.partials.context-nav')

    <div class="mb-6 flex flex-wrap gap-2">
        <x-ui.button href="{{ route($questionsRoutePrefix.'.edit', [$listeningTest, $section, $group, $question]) }}">Edit</x-ui.button>
        @can('delete', $question)
            <form method="POST" action="{{ route($questionsRoutePrefix.'.destroy', [$listeningTest, $section, $group, $question]) }}" onsubmit="return confirm('Delete this question?')">
                @csrf
                @method('DELETE')
                <x-ui.button type="submit" variant="danger">Delete</x-ui.button>
            </form>
        @endcan
    </div>
    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card title="Question">
            <dl class="space-y-2 text-sm">
                <div><dt class="aa-muted">Number</dt><dd>Q{{ $question->question_number }}</dd></div>
                <div><dt class="aa-muted">Type</dt><dd>{{ $question->question_type?->label() }}</dd></div>
                <div><dt class="aa-muted">Text</dt><dd class="whitespace-pre-wrap">{{ $question->question_text ?: '—' }}</dd></div>
                <div><dt class="aa-muted">Marks</dt><dd>{{ $question->marks }}</dd></div>
            </dl>
        </x-ui.card>
        <x-ui.card title="Readiness">
            <dl class="grid gap-2 text-sm sm:grid-cols-2">
                <div><dt class="aa-muted">Correct Answer</dt><dd>{{ $readiness['has_correct_answer'] ? 'Yes' : 'No' }}</dd></div>
                <div><dt class="aa-muted">Valid Timestamp</dt><dd>{{ $readiness['has_valid_timestamp'] ? 'Yes' : 'No' }}</dd></div>
                <div><dt class="aa-muted">Ready</dt><dd><x-ui.badge :tone="$readiness['is_ready'] ? 'green' : 'amber'">{{ $readiness['is_ready'] ? 'Yes' : 'No' }}</x-ui.badge></dd></div>
            </dl>
        </x-ui.card>
    </div>
    <x-ui.card title="Correct Answer" class="mt-6">
        @include('admin.listening.questions.partials.answer-display')
    </x-ui.card>
</x-layouts.admin>
