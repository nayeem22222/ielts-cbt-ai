<x-layouts.admin
    :title="$group->title.' — Labelling Builder'"
    :heading="$group->title"
    eyebrow="Labelling Builder"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard')],
        ['label' => 'Listening Tests', 'href' => route('admin.listening.tests.index')],
        ['label' => $listeningTest->title, 'href' => route('admin.listening.tests.builder.index', ['listeningTest' => $listeningTest, 'section' => $section->id, 'question_group' => $group->id])],
        ['label' => 'Labelling Questions'],
    ]"
>
    @push('head')
        @vite(['resources/js/reading-diagram-builder.js'])
    @endpush

    <div
        x-data="readingDiagramBuilder(@js([
            'groupId' => $group->id,
            'startQuestion' => $group->start_question,
            'endQuestion' => $group->end_question,
            'answerRule' => $settings['answer_rule'],
            'customAnswerRule' => $settings['custom_answer_rule'],
            'diagramImageUrl' => $diagramImageUrl,
            'labels' => collect($settings['labels'])->map(function ($label) use ($questions) {
                $question = $questions->firstWhere('question_number', (int) ($label['question_number'] ?? 0));
                $correct = $question?->correctAnswers->first();

                return [
                    'question_number' => (int) ($label['question_number'] ?? 0),
                    'x' => (float) ($label['x'] ?? 0),
                    'y' => (float) ($label['y'] ?? 0),
                    'label' => $label['label'] ?? '',
                    'question_id' => $question?->id,
                    'correct_answer' => $correct?->answer ?? '',
                    'alternative_answers' => $question?->alternativeAnswers ?? [],
                    'case_sensitive' => $question?->case_sensitive ?? false,
                    'explanation' => $question?->explanation ?? '',
                    'difficulty' => $question?->difficulty ?? 'medium',
                    'reference_type' => $question?->reference_type ?? '',
                    'reference_phrase' => $question?->reference_phrase ?? '',
                    'reference_sentence' => $question?->reference_sentence ?? '',
                    'reference_paragraph' => $question?->reference_paragraph ?? $question?->paragraph_reference ?? '',
                    'reference_start_offset' => $question?->reference_start_offset ?? '',
                    'reference_end_offset' => $question?->reference_end_offset ?? '',
                ];
            })->values()->all(),
            'saveLabelsUrl' => route('admin.listening-question-groups.labelling-questions.labels', $group),
            'uploadUrl' => route('admin.listening-question-groups.labelling-questions.upload', $group),
            'confirmRemove' => session('diagram_confirm_remove', false),
            'destroyQuestionBase' => url('/admin/listening-labelling-questions'),
        ]))"
        x-init="init()"
    >
        @include('admin.listening.question-builders.labelling.partials.header')

        <div class="mb-4 flex flex-wrap gap-2">
            <x-ui.button href="{{ route('admin.listening-question-groups.labelling-questions.edit', $group) }}">Edit Builder</x-ui.button>
            <x-ui.button href="{{ route('admin.listening-question-groups.labelling-questions.preview', $group) }}" variant="outline">Preview</x-ui.button>
            <x-ui.button href="{{ route('admin.listening.tests.builder.index', ['listeningTest' => $listeningTest, 'section' => $section->id, 'question_group' => $group->id]) }}" variant="outline">Back to Group</x-ui.button>
        </div>

        @if (session('status'))
            <x-ui.alert tone="green" class="mb-4">{{ session('status') }}</x-ui.alert>
        @endif

        @include('admin.listening.question-builders.partials.validation-errors')

        @if (session('diagram_confirm_remove') || $errors->has('confirm_remove'))
            <x-ui.alert tone="amber" class="mb-4">
                {{ $errors->first('confirm_remove') ?: 'Removing labels will delete linked questions. Confirm to continue.' }}
            </x-ui.alert>
        @endif

        @if ($showPreview)
            @include('admin.listening.question-builders.labelling.partials.preview', [
                'group' => $group,
                'questions' => $questions,
                'settings' => $settings,
                'diagramImageUrl' => $diagramImageUrl,
                'answerRules' => $answerRules,
            ])
        @else
            <div class="grid gap-6 xl:grid-cols-2">
                <div class="space-y-4">
                    @include('admin.listening.question-builders.labelling.partials.diagram-canvas', ['group' => $group])
                </div>
                <div class="space-y-4">
                    @include('admin.listening.question-builders.labelling.partials.label-panel', [
                        'group' => $group,
                        'questions' => $questions,
                        'answerRules' => $answerRules,
                        'settings' => $settings,
                    ])
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
