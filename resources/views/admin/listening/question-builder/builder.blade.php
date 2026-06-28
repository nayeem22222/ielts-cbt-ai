@php
    $builderConfig = [
        'expandedSections' => $sections->pluck('id')->all(),
        'instructionDefaults' => $instructionDefaults,
        'questionTypeLabels' => collect($questionTypes)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all(),
    ];

    if ($selectedGroup && $selectedSection) {
        $builderConfig = array_merge($builderConfig, [
            'groupTitle' => old('title', $selectedGroup->title),
            'groupInstruction' => old('instruction', $selectedGroup->instruction ?? ''),
            'groupQuestionType' => old('question_type', $selectedGroup->question_type?->value),
            'groupQuestionTypeLabel' => $selectedGroup->question_type?->label() ?? '',
            'groupStart' => (int) old('start_question_number', $selectedGroup->start_question_number),
            'groupEnd' => (int) old('end_question_number', $selectedGroup->end_question_number),
        ]);
    }
@endphp

<x-layouts.admin :title="$listeningTest->title.' Builder'" :heading="$listeningTest->title" eyebrow="Listening Test Builder" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Listening Tests', 'href' => route($routePrefix.'.index')], ['label' => 'Builder']]">
    @push('head')
        @vite(['resources/js/listening-section-builder.js'])
    @endpush

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm aa-muted">ID {{ $listeningTest->id }} · {{ $listeningTest->test_code }} · {{ $listeningTest->duration_minutes }} minutes · {{ $sections->count() }} {{ Str::plural('section', $sections->count()) }}</p>
            <h2 class="text-2xl font-bold text-neutral-900 dark:text-white">{{ $listeningTest->title }}</h2>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-ui.button href="{{ route($routePrefix.'.show', $listeningTest) }}" variant="outline">Test Overview</x-ui.button>
            <x-ui.button href="{{ route($routePrefix.'.edit', $listeningTest) }}" variant="outline">Test Settings</x-ui.button>
            <x-ui.button href="{{ route($sectionsRoutePrefix.'.index', $listeningTest) }}" variant="outline">Manage Sections</x-ui.button>
        </div>
    </div>

    @if (session('status'))
        <x-ui.alert tone="green" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    @if (session('error'))
        <x-ui.alert tone="red" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    @include('admin.listening.question-builder.partials.builder-validation-panel', ['summary' => $summary])

    <div
        class="grid min-w-0 items-start gap-4 xl:grid-cols-[minmax(280px,360px)_minmax(0,1fr)] xl:gap-6"
        data-listening-test-builder
        x-data="listeningTestBuilder(@js($builderConfig))"
        x-init="init()"
    >
        <aside class="order-2 min-w-0 space-y-4 xl:order-1">
            <x-ui.card title="Listening Test" padding="p-5">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs uppercase aa-muted">Status</dt>
                        <dd>@include('admin.listening.tests.partials.status-badge', ['status' => $listeningTest->status])</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase aa-muted">Questions</dt>
                        <dd class="font-medium">{{ $summary['questions_count'] }}/{{ $summary['expected_questions'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase aa-muted">Groups</dt>
                        <dd class="font-medium">{{ $summary['groups_count'] }}</dd>
                    </div>
                </dl>
            </x-ui.card>

            <x-ui.card title="Sections & Question Groups" padding="p-5">
                <div id="section-sortable-list" class="space-y-3">
                    @forelse ($sections as $sectionItem)
                        @include('admin.listening.question-builder.partials.section-with-groups-sidebar', [
                            'sectionItem' => $sectionItem,
                            'listeningTest' => $listeningTest,
                            'selectedSection' => $selectedSection?->id === $sectionItem->id,
                            'requestedGroupId' => $requestedGroupId ?? 0,
                            'isFirst' => $loop->first,
                            'isLast' => $loop->last,
                        ])
                    @empty
                        <x-ui.empty-state title="No sections yet">
                            Create sections before adding question groups.
                            <x-ui.button class="mt-4" href="{{ route($sectionsRoutePrefix.'.index', $listeningTest) }}">Manage Sections</x-ui.button>
                        </x-ui.empty-state>
                    @endforelse
                </div>
            </x-ui.card>
        </aside>

        <section class="order-1 min-w-0 space-y-4 xl:order-2">
            @if ($activePanel === 'group' && $selectedGroup && $selectedSection)
                @include('admin.listening.question-builder.partials.group-editor', [
                    'listeningTest' => $listeningTest,
                    'section' => $selectedSection,
                    'group' => $selectedGroup,
                    'questionTypes' => $questionTypes,
                    'enabledQuestionTypes' => $enabledQuestionTypes ?? $questionTypes,
                    'questionTypeSchemas' => $questionTypeSchemas ?? [],
                    'instructionDefaults' => $instructionDefaults,
                    'audios' => $audios ?? [],
                    'availableRanges' => $availableRanges ?? [],
                ])
            @elseif ($selectedSection)
                @include('admin.listening.question-builder.partials.section-summary-panel', [
                    'listeningTest' => $listeningTest,
                    'section' => $selectedSection,
                ])
            @else
                <x-ui.empty-state title="Select a section or question group">
                    Choose a section from the sidebar, or click <strong>Add Question Group</strong> to start building.
                </x-ui.empty-state>
            @endif
        </section>
    </div>
</x-layouts.admin>
