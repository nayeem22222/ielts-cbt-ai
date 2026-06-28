<x-layouts.admin :title="$listeningTest->title.' — Question Builder'" :heading="'Question Builder'" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Listening Tests', 'href' => route($routePrefix.'.index')], ['label' => $listeningTest->title, 'href' => route($routePrefix.'.show', $listeningTest)], ['label' => 'Question Builder']]">
    @include('admin.listening.sections.partials.alerts')

    <div class="mb-6 flex justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold">{{ $listeningTest->title }}</h2>
            <p class="text-sm aa-muted">{{ $listeningTest->test_code }} · @include('admin.listening.tests.partials.status-badge', ['status' => $listeningTest->status])</p>
        </div>
        <x-ui.button href="{{ route($routePrefix.'.show', $listeningTest) }}" variant="outline">Back to Test</x-ui.button>
    </div>

    @include('admin.listening.question-builder.partials.builder-summary', ['summary' => $summary])

    <div class="mb-6 rounded-2xl border border-neutral-200 bg-white p-4 text-sm dark:border-neutral-800 dark:bg-neutral-900/40">
        <p class="font-medium">Quick start</p>
        <p class="mt-1 aa-muted">Open a section below → add question groups → bulk-create questions → edit answers. Each section needs {{ config('listening.questions.questions_per_section', 10) }} questions before publish.</p>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        @foreach ($summary['sections'] as $sectionSummary)
            <x-ui.card :title="'Section '.$sectionSummary['section_number'].' — '.$sectionSummary['title']">
                <p class="mb-3 text-sm aa-muted">Official range Q{{ $sectionSummary['range']['start'] }}–Q{{ $sectionSummary['range']['end'] }}</p>
                <dl class="grid gap-2 text-sm sm:grid-cols-2">
                    <div><dt class="aa-muted">Groups</dt><dd>{{ $sectionSummary['groups_count'] }}</dd></div>
                    <div><dt class="aa-muted">Questions</dt><dd>{{ $sectionSummary['questions_count'] }}/{{ $sectionSummary['expected_questions'] }}</dd></div>
                    <div><dt class="aa-muted">Audio</dt><dd>{{ $sectionSummary['has_audio'] ? 'Yes' : 'No' }}</dd></div>
                    <div><dt class="aa-muted">Transcript</dt><dd>{{ $sectionSummary['has_transcript'] ? 'Yes' : 'No' }}</dd></div>
                </dl>
                @if (! empty($sectionSummary['missing_numbers']))
                    <p class="mt-3 text-xs text-amber-700 dark:text-amber-200">Missing: {{ implode(', ', $sectionSummary['missing_numbers']) }}</p>
                @endif
                <div class="mt-4 flex flex-wrap gap-2">
                    <x-ui.button href="{{ route($sectionsRoutePrefix.'.builder.index', [$listeningTest, $sectionSummary['section_id']]) }}" size="sm">Open Section Builder</x-ui.button>
                </div>
            </x-ui.card>
        @endforeach
    </div>
</x-layouts.admin>
