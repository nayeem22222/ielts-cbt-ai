<x-layouts.admin
    :title="$group->title.' — Completion Builder'"
    :heading="$group->title"
    eyebrow="Completion Question Builder"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard')],
        ['label' => 'Reading Tests', 'href' => route('admin.reading-tests.index')],
        ['label' => $test->title, 'href' => route('admin.reading-tests.builder', ['readingTest' => $test, 'passage' => $passage->id, 'question_group' => $group->id])],
        ['label' => 'Completion Questions'],
    ]"
>
    @push('head')
        @vite(['resources/js/reading-completion-builder.js'])
    @endpush

    <div
        x-data="readingCompletionBuilder(@js([
            'detectUrl' => route('admin.reading-question-groups.completion-questions.detect', $group),
            'groupStart' => $group->start_question,
            'groupEnd' => $group->end_question,
            'type' => $type->value,
            'templateHtml' => $settings['template_html'],
            'tableData' => $settings['table_data'] ?? ['rows' => []],
            'flowSteps' => $settings['flow_steps'] ?? [],
            'answerRule' => $settings['answer_rule'],
            'customAnswerRule' => $settings['custom_answer_rule'],
            'existingQuestionNumbers' => $existingQuestionNumbers,
            'detectedCount' => $detectedCount,
            'confirmRemove' => session('completion_confirm_remove', false),
        ]))"
        x-init="init()"
    >
        @include('pages.admin.reading-tests.completion.partials.header', [
            'test' => $test,
            'passage' => $passage,
            'group' => $group,
            'type' => $type,
            'detectedCount' => $detectedCount,
            'expectedCount' => $expectedCount,
        ])

        <div class="mb-4 flex flex-wrap gap-2">
            <x-ui.button href="{{ route('admin.reading-question-groups.completion-questions.edit', $group) }}">Edit Builder</x-ui.button>
            <x-ui.button href="{{ route('admin.reading-question-groups.completion-questions.preview', $group) }}" variant="outline">Preview</x-ui.button>
            <x-ui.button href="{{ route('admin.reading-tests.builder', ['readingTest' => $test, 'passage' => $passage->id, 'question_group' => $group->id]) }}" variant="outline">Back to Group</x-ui.button>
        </div>

        @if (session('status'))
            <x-ui.alert tone="green" class="mb-4">{{ session('status') }}</x-ui.alert>
        @endif

        @if ($showPreview)
            @include('pages.admin.reading-tests.completion.partials.preview', [
                'group' => $group,
                'questions' => $questions,
                'settings' => $settings,
                'previewHtml' => $previewHtml,
                'answerRules' => $answerRules,
            ])
        @else
            @if ($type->usesCompletionTemplate())
                @include('pages.admin.reading-tests.completion.partials.live-detection')
            @endif

            @if ($type->value === 'table_completion')
                @include('pages.admin.reading-tests.completion.partials.table-builder', [
                    'group' => $group,
                    'settings' => $settings,
                    'answerRules' => $answerRules,
                    'questions' => $questions,
                ])
            @elseif ($type->value === 'flow_chart_completion')
                @include('pages.admin.reading-tests.completion.partials.flowchart-builder', [
                    'group' => $group,
                    'settings' => $settings,
                    'answerRules' => $answerRules,
                    'questions' => $questions,
                ])
            @elseif ($type->value === 'summary_completion')
                @include('pages.admin.reading-tests.completion.partials.template-builder', [
                    'group' => $group,
                    'settings' => $settings,
                    'answerRules' => $answerRules,
                    'questions' => $questions,
                    'editorLabel' => 'Summary Content',
                    'editorId' => 'completion_template_html',
                ])
            @elseif ($type->value === 'note_completion')
                @include('pages.admin.reading-tests.completion.partials.template-builder', [
                    'group' => $group,
                    'settings' => $settings,
                    'answerRules' => $answerRules,
                    'questions' => $questions,
                    'editorLabel' => 'Notes Content',
                    'editorId' => 'completion_template_html',
                    'noteMode' => true,
                ])
            @elseif ($type->value === 'sentence_completion')
                @include('pages.admin.reading-tests.completion.partials.sentence-builder', [
                    'group' => $group,
                    'settings' => $settings,
                    'answerRules' => $answerRules,
                    'questions' => $questions,
                ])
            @endif

            @include('pages.admin.reading-tests.completion.partials.bulk-import', [
                'group' => $group,
                'type' => $type,
            ])
        @endif
    </div>
</x-layouts.admin>
