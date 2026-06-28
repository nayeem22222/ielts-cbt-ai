<x-layouts.admin :title="$section->title.' — Section Builder'" :heading="$section->title" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Listening Tests', 'href' => route($routePrefix.'.index')], ['label' => $listeningTest->title, 'href' => route($routePrefix.'.show', $listeningTest)], ['label' => 'Question Builder', 'href' => route($builderRoutePrefix.'.index', $listeningTest)], ['label' => $section->title]]">
    @include('admin.listening.sections.partials.alerts')

    @include('admin.listening.question-builder.partials.workflow-steps', ['current' => 'section'])

    <div class="mb-6 flex flex-wrap justify-between gap-4">
        <div>
            <p class="text-sm aa-muted">Section {{ $section->section_number }} · Official range Q{{ $section->start_question_number }}–Q{{ $section->end_question_number }}</p>
            <p class="mt-1 text-sm aa-muted">Start here: add a group, bulk-create questions, then set answers.</p>
        </div>
        <div class="flex gap-2">
            @include('admin.listening.question-groups.partials.add-group-button', ['size' => 'md', 'variant' => 'primary'])
            <x-ui.button href="{{ route($groupsRoutePrefix.'.create', [$listeningTest, $section]) }}" variant="outline">Custom Group</x-ui.button>
            <x-ui.button href="{{ route($builderRoutePrefix.'.index', $listeningTest) }}" variant="outline">Test Overview</x-ui.button>
        </div>
    </div>

    @include('admin.listening.question-builder.partials.readiness-card', ['summary' => $summary])

    <div class="mt-6 space-y-4">
        @php
            $sectionGroupModels = \App\Models\Listening\ListeningQuestionGroup::query()
                ->whereIn('id', collect($summary['groups'] ?? [])->pluck('id')->filter()->all())
                ->get()
                ->keyBy('id');
        @endphp
        @forelse ($summary['groups'] as $groupSummary)
            @include('admin.listening.question-groups.partials.group-card', [
                'group' => $sectionGroupModels->get($groupSummary['id']),
                'groupSummary' => $groupSummary,
            ])
        @empty
            <x-ui.empty-state title="No question groups yet">
                No question groups yet. Add your first group to start building Q{{ $section->start_question_number }}–Q{{ $section->end_question_number }}.
            </x-ui.empty-state>
            <div class="mt-4">
                @include('admin.listening.question-groups.partials.add-group-button', ['variant' => 'primary'])
            </div>
        @endforelse
    </div>
</x-layouts.admin>
