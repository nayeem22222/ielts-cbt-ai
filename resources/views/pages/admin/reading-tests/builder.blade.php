@php
    $builderConfig = [
        'editorId' => 'content_html',
        'sortableId' => 'passage-sortable-list',
        'reorderFormId' => 'passage-reorder-form',
        'previewHtml' => $selectedPassage?->content_html ?? '',
        'autoLabels' => $selectedPassage?->auto_paragraph_labels ?? true,
        'expandedPassages' => $passages->pluck('id')->all(),
        'instructionDefaults' => $instructionDefaults,
        'questionTypeLabels' => collect($questionTypes)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all(),
    ];

    if ($selectedGroup) {
        $builderConfig = array_merge($builderConfig, [
            'groupTitle' => old('title', $selectedGroup->title),
            'groupInstruction' => old('instruction', $selectedGroup->instruction ?? ''),
            'groupQuestionType' => old('question_type', $selectedGroup->question_type?->value),
            'groupQuestionTypeLabel' => $selectedGroup->question_type?->label() ?? '',
            'groupStart' => (int) old('start_question', $selectedGroup->start_question),
            'groupEnd' => (int) old('end_question', $selectedGroup->end_question),
        ]);
    }
@endphp

<x-layouts.admin :title="$test->title.' Builder'" :heading="$test->title" eyebrow="Reading Test Builder" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Reading Tests', 'href' => route('admin.reading-tests.index')], ['label' => 'Builder']]">
    @push('head')
        @vite(['resources/js/reading-passage-builder.js'])
    @endpush

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm aa-muted">ID {{ $test->id }} · {{ $test->exam_type?->label() }} · {{ $test->duration_minutes }} minutes · {{ $passages->count() }} {{ Str::plural('passage', $passages->count()) }}</p>
            <h2 class="text-2xl font-bold text-neutral-900 dark:text-white">{{ $test->title }}</h2>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-ui.button href="{{ route('admin.reading-tests.preview', $test) }}" variant="outline">Test Preview</x-ui.button>
            <x-ui.button href="{{ route('admin.reading-tests.edit', $test) }}" variant="outline">Test Settings</x-ui.button>
        </div>
    </div>

    <div
        class="grid gap-6 xl:grid-cols-[360px_minmax(0,1fr)]"
        x-data="readingTestBuilder(@js($builderConfig))"
        x-init="init()"
    >
        <aside class="order-2 space-y-4 xl:order-1">
            <x-ui.card title="Reading Test" padding="p-5">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs uppercase aa-muted">Exam Type</dt>
                        <dd class="font-medium">{{ $test->exam_type?->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase aa-muted">Status</dt>
                        <dd><x-ui.badge :tone="$test->status === \App\Enums\Course\PublishStatus::Published ? 'green' : 'amber'">{{ $test->status?->label() }}</x-ui.badge></dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase aa-muted">Duration</dt>
                        <dd class="font-medium">{{ $test->duration_minutes }} minutes</dd>
                    </div>
                </dl>
            </x-ui.card>

            <x-ui.card title="Passages & Question Groups" padding="p-5">
                <form method="POST" action="{{ route('admin.reading-tests.passages.store', $test) }}" class="mb-4">
                    @csrf
                    <x-ui.button type="submit" class="w-full">Add Passage</x-ui.button>
                </form>

                <form id="passage-reorder-form" method="POST" action="{{ route('admin.reading-tests.passages.reorder', $test) }}">
                    @csrf
                    <div data-passage-ids>
                        @foreach ($passages as $passage)
                            <input type="hidden" name="passage_ids[]" value="{{ $passage->id }}">
                        @endforeach
                    </div>
                </form>

                <div id="passage-sortable-list" class="space-y-3">
                    @forelse ($passages as $passage)
                        @include('pages.admin.reading-tests.partials.passage-with-groups-sidebar', [
                            'passage' => $passage,
                            'test' => $test,
                            'selectedPassage' => $selectedPassage?->id === $passage->id,
                            'requestedGroupId' => $requestedGroupId ?? 0,
                            'isFirst' => $loop->first,
                            'isLast' => $loop->last,
                        ])
                    @empty
                        <x-ui.empty-state title="No passages yet">Add your first passage to start building this reading test.</x-ui.empty-state>
                    @endforelse
                </div>
            </x-ui.card>
        </aside>

        <section class="order-1 space-y-6 xl:order-2 xl:sticky xl:top-6 xl:max-h-[calc(100vh-7rem)] xl:self-start xl:overflow-y-auto">
            @if ($activePanel === 'group' && $selectedGroup && $selectedPassage)
                @include('pages.admin.reading-tests.partials.group-editor', [
                    'test' => $test,
                    'passage' => $selectedPassage,
                    'group' => $selectedGroup,
                    'questionTypes' => $questionTypes,
                    'groupStatuses' => $passageStatuses,
                ])
            @elseif ($selectedPassage)
                @include('pages.admin.reading-tests.partials.passage-editor', [
                    'test' => $test,
                    'selectedPassage' => $selectedPassage,
                    'passageStatuses' => $passageStatuses,
                ])
            @else
                <x-ui.card title="Passage Editor">
                    <x-ui.empty-state title="Select or add a passage">
                        Use the sidebar to add a passage or question group, then edit it here.
                    </x-ui.empty-state>
                </x-ui.card>
            @endif
        </section>
    </div>
</x-layouts.admin>
