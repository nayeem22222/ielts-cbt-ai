<x-layouts.admin :title="$group->title ?: 'Question Group'" :heading="$group->title ?: 'Question Group'" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Listening Tests', 'href' => route($routePrefix.'.index')], ['label' => $listeningTest->title, 'href' => route($routePrefix.'.show', $listeningTest)], ['label' => 'Question Builder', 'href' => route($builderRoutePrefix.'.index', $listeningTest)], ['label' => 'Section '.$section->section_number, 'href' => route($sectionsRoutePrefix.'.builder.index', [$listeningTest, $section])], ['label' => 'View']]">
    @include('admin.listening.sections.partials.alerts')

    @include('admin.listening.question-builder.partials.context-nav')

    @if (($readiness['questions_count'] ?? 0) < ($readiness['expected_questions'] ?? 0))
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/40 dark:bg-amber-950/20">
            <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">Next step: create questions for this group</p>
            <p class="mt-1 text-sm text-amber-800 dark:text-amber-200">
                You have {{ $readiness['questions_count'] }}/{{ $readiness['expected_questions'] }} questions.
                Bulk-create placeholders for Q{{ $group->start_question_number }}–Q{{ $group->end_question_number }}, then set each answer.
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                @include('admin.listening.questions.partials.bulk-create-form', ['bulkGroup' => $group, 'variant' => 'primary'])
                <x-ui.button href="{{ route($questionsRoutePrefix.'.index', [$listeningTest, $section, $group]) }}" variant="outline">Manage Questions</x-ui.button>
            </div>
        </div>
    @endif

    @include('admin.listening.question-builder.partials.workflow-steps', ['current' => 'group'])

    <div class="mb-6 flex flex-wrap gap-2">
        <x-ui.button href="{{ route($groupsRoutePrefix.'.edit', [$listeningTest, $section, $group]) }}">Edit Group</x-ui.button>
        <x-ui.button href="{{ route($questionsRoutePrefix.'.index', [$listeningTest, $section, $group]) }}" variant="outline">Manage Questions</x-ui.button>
        @include('admin.listening.questions.partials.bulk-create-form', ['bulkGroup' => $group])
        @can('create', [\App\Models\Listening\ListeningQuestionGroup::class, $listeningTest, $section])
            <form method="POST" action="{{ route($groupsRoutePrefix.'.duplicate', [$listeningTest, $section, $group]) }}">
                @csrf
                <x-ui.button type="submit" variant="outline">Duplicate Group</x-ui.button>
            </form>
        @endcan
        @can('delete', $group)
            <form method="POST" action="{{ route($groupsRoutePrefix.'.destroy', [$listeningTest, $section, $group]) }}" onsubmit="return confirm('Delete this question group and its questions?')">
                @csrf
                @method('DELETE')
                <x-ui.button type="submit" variant="danger">Delete</x-ui.button>
            </form>
        @endcan
    </div>
    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card title="Group Details">
            <dl class="space-y-2 text-sm">
                <div><dt class="aa-muted">Type</dt><dd>{{ $group->question_type?->label() }}</dd></div>
                <div><dt class="aa-muted">Range</dt><dd>Q{{ $group->start_question_number }}–Q{{ $group->end_question_number }} ({{ $group->total_questions }} questions)</dd></div>
                <div><dt class="aa-muted">Layout</dt><dd>{{ $group->layout_type?->label() }}</dd></div>
                <div><dt class="aa-muted">Instruction</dt><dd class="whitespace-pre-wrap">{{ $group->instruction ?: '—' }}</dd></div>
            </dl>
        </x-ui.card>
        <x-ui.card title="Group Readiness">
            <dl class="grid gap-2 text-sm sm:grid-cols-2">
                <div><dt class="aa-muted">Valid Range</dt><dd>{{ $readiness['has_valid_range'] ? 'Yes' : 'No' }}</dd></div>
                <div><dt class="aa-muted">Questions</dt><dd>{{ $readiness['questions_count'] }}/{{ $readiness['expected_questions'] }}</dd></div>
                <div><dt class="aa-muted">Ready Questions</dt><dd>{{ $readiness['questions_ready_count'] }}</dd></div>
                <div><dt class="aa-muted">Ready</dt><dd><x-ui.badge :tone="$readiness['is_ready'] ? 'green' : 'amber'">{{ $readiness['is_ready'] ? 'Yes' : 'No' }}</x-ui.badge></dd></div>
            </dl>
        </x-ui.card>
    </div>
    @include('admin.listening.question-groups.partials.transcript-preview', ['group' => $group])
    @include('admin.listening.question-types.partials.type-preview', ['preview' => $preview ?? null, 'group' => $group])
</x-layouts.admin>
