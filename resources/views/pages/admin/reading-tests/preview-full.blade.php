<x-layouts.admin
    :title="$test->title.' — Full Preview'"
    :heading="$test->title"
    eyebrow="Reading Test Full Preview"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard')],
        ['label' => 'Reading Tests', 'href' => route('admin.reading-tests.index')],
        ['label' => $test->title, 'href' => route('admin.reading-tests.builder', $test)],
        ['label' => 'Full Preview'],
    ]"
>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm aa-muted">{{ $test->exam_type?->label() }} · {{ $test->duration_minutes }} minutes</p>
            <h2 class="text-2xl font-bold text-neutral-900 dark:text-white">{{ $test->title }}</h2>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-ui.button href="{{ route('admin.reading-tests.preview-full', ['readingTest' => $test, 'answers' => $showCorrectAnswers ? 0 : 1, 'explanations' => $showExplanations ? 1 : 0]) }}" variant="outline">
                {{ $showCorrectAnswers ? 'Hide' : 'Show' }} Correct Answers
            </x-ui.button>
            <x-ui.button href="{{ route('admin.reading-tests.preview-full', ['readingTest' => $test, 'answers' => $showCorrectAnswers ? 1 : 0, 'explanations' => $showExplanations ? 0 : 1]) }}" variant="outline">
                {{ $showExplanations ? 'Hide' : 'Show' }} Explanations
            </x-ui.button>
            <x-ui.button href="{{ route('admin.reading-tests.builder', $test) }}" variant="outline">Back to Builder</x-ui.button>
        </div>
    </div>

    <div class="space-y-8">
        @foreach ($test->passages as $passage)
            <section class="space-y-4">
                <x-ui.card title="Passage {{ $passage->part_number }}: {{ $passage->title }}" subtitle="Questions {{ $passage->start_question }}–{{ $passage->end_question }}">
                    @if ($passage->instruction)
                        <p class="mb-4 text-sm italic aa-muted">{{ $passage->instruction }}</p>
                    @endif

                    <x-reading.passage-preview :passage="$passage" />
                </x-ui.card>

                <div class="space-y-4">
                    @foreach ($passage->groups as $group)
                        <x-admin.reading-preview.group
                            :group="$group"
                            :show-correct-answers="$showCorrectAnswers"
                            :show-explanations="$showExplanations"
                            :answer-rules="$answerRules"
                        />
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</x-layouts.admin>
