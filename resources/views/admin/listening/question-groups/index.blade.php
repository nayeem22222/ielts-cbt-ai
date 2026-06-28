<x-layouts.admin title="Question Groups" heading="Question Groups" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Listening Tests', 'href' => route($routePrefix.'.index')], ['label' => $listeningTest->title, 'href' => route($routePrefix.'.show', $listeningTest)], ['label' => 'Section '.$section->section_number, 'href' => route($sectionsRoutePrefix.'.builder.index', [$listeningTest, $section])], ['label' => 'Groups']]">
    @include('admin.listening.sections.partials.alerts')

    <div class="mb-4 rounded-xl border border-blue-100 bg-blue-50/70 p-3 text-sm dark:border-blue-900/40 dark:bg-blue-950/20">
        Tip: use <a class="font-medium text-brand-600 hover:underline" href="{{ route($sectionsRoutePrefix.'.builder.index', [$listeningTest, $section]) }}">Section Builder</a> as your main workspace — it shows readiness and next steps in one place.
    </div>

    <div class="mb-6 flex justify-between">
        @can('create', [\App\Models\Listening\ListeningQuestionGroup::class, $listeningTest, $section])
            <x-ui.button href="{{ route($groupsRoutePrefix.'.create', [$listeningTest, $section]) }}">Add Group</x-ui.button>
        @endcan
        <x-ui.button href="{{ route($sectionsRoutePrefix.'.builder.index', [$listeningTest, $section]) }}" variant="outline">Section Builder</x-ui.button>
    </div>
    <div class="space-y-4">
        @forelse ($groups as $group)
            @php
                $groupQuestions = $group->questions()->where('is_active', true)->pluck('question_number')->map(fn ($n) => (int) $n)->all();
                $missing = [];
                for ($i = (int) $group->start_question_number; $i <= (int) $group->end_question_number; $i++) {
                    if (! in_array($i, $groupQuestions, true)) {
                        $missing[] = $i;
                    }
                }
            @endphp
            @include('admin.listening.question-groups.partials.group-card', [
                'group' => $group,
                'groupSummary' => [
                    'id' => $group->id,
                    'title' => $group->title,
                    'question_type' => $group->question_type?->value,
                    'question_type_label' => $group->question_type?->label(),
                    'start' => $group->start_question_number,
                    'end' => $group->end_question_number,
                    'total_questions' => $group->total_questions,
                    'questions_count' => $group->questions_count ?? 0,
                    'layout_type' => $group->layout_type?->value,
                    'missing_numbers' => $missing,
                ],
            ])
        @empty
            <x-ui.empty-state title="No question groups yet">No question groups yet. Add your first group.</x-ui.empty-state>
            <div class="mt-4">
                @include('admin.listening.question-groups.partials.add-group-button', ['variant' => 'primary'])
            </div>
        @endforelse
    </div>
</x-layouts.admin>
