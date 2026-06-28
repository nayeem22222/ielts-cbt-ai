<x-layouts.admin
    :title="$group->title.' — Completion Builder'"
    :heading="$group->title"
    eyebrow="Completion Question Builder"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard')],
        ['label' => 'Listening Tests', 'href' => route('admin.listening.tests.index')],
        ['label' => $listeningTest->title, 'href' => route('admin.listening.tests.builder.index', ['listeningTest' => $listeningTest, 'section' => $section->id, 'question_group' => $group->id])],
        ['label' => 'Completion Questions'],
    ]"
>
    @push('head')
        @vite(['resources/js/reading-completion-builder.js'])
    @endpush

    <div
        x-data="readingCompletionBuilder(@js([
            'detectUrl' => route('admin.listening-question-groups.completion-questions.detect', $group),
            'groupStart' => $group->start_question,
            'groupEnd' => $group->end_question,
            'type' => $type->value === 'flowchart_completion' ? 'flow_chart_completion' : $type->value,
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
        @include('admin.listening.question-builders.completion.partials.header', [
            'test' => $listeningTest,
            'section' => $section,
            'group' => $group,
            'type' => $type,
            'detectedCount' => $detectedCount,
            'expectedCount' => $expectedCount,
        ])

        <div class="mb-4 flex flex-wrap gap-2">
            <x-ui.button href="{{ route('admin.listening-question-groups.completion-questions.edit', $group) }}">Edit Builder</x-ui.button>
            <x-ui.button href="{{ route('admin.listening-question-groups.completion-questions.preview', $group) }}" variant="outline">Preview</x-ui.button>
            <x-ui.button href="{{ route('admin.listening.tests.builder.index', ['listeningTest' => $listeningTest, 'section' => $section->id, 'question_group' => $group->id]) }}" variant="outline">Back to Group</x-ui.button>
        </div>

        @if (session('status'))
            <x-ui.alert tone="green" class="mb-4">{{ session('status') }}</x-ui.alert>
        @endif

        @if ($showPreview)
            @include('admin.listening.question-builders.completion.partials.preview', [
                'group' => $group,
                'questions' => $questions,
                'settings' => $settings,
                'previewHtml' => $previewHtml,
                'answerRules' => $answerRules,
            ])
        @else
            @if ($type->usesCompletionTemplate())
                @include('admin.listening.question-builders.completion.partials.live-detection')
            @endif

            @if ($type->value === 'table_completion')
                @include('admin.listening.question-builders.completion.partials.table-builder', [
                    'group' => $group,
                    'settings' => $settings,
                    'answerRules' => $answerRules,
                    'questions' => $questions,
                ])
            @elseif ($type->value === 'flowchart_completion')
                @include('admin.listening.question-builders.completion.partials.flowchart-builder', [
                    'group' => $group,
                    'settings' => $settings,
                    'answerRules' => $answerRules,
                    'questions' => $questions,
                ])
            @elseif ($type->value === 'summary_completion')
                @include('admin.listening.question-builders.completion.partials.template-builder', [
                    'group' => $group,
                    'settings' => $settings,
                    'answerRules' => $answerRules,
                    'questions' => $questions,
                    'editorLabel' => 'Summary Content',
                    'editorId' => 'completion_template_html',
                ])
            @elseif ($type->value === 'note_completion')
                @include('admin.listening.question-builders.completion.partials.template-builder', [
                    'group' => $group,
                    'settings' => $settings,
                    'answerRules' => $answerRules,
                    'questions' => $questions,
                    'editorLabel' => 'Notes Content',
                    'editorId' => 'completion_template_html',
                    'noteMode' => true,
                ])
            @elseif ($type->value === 'sentence_completion')
                @include('admin.listening.question-builders.completion.partials.sentence-builder', [
                    'group' => $group,
                    'settings' => $settings,
                    'answerRules' => $answerRules,
                    'questions' => $questions,
                ])
            @elseif ($type->value === 'form_completion')
                @include('admin.listening.question-builders.completion.partials.template-builder', [
                    'group' => $group,
                    'settings' => $settings,
                    'answerRules' => $answerRules,
                    'questions' => $questions,
                    'editorLabel' => 'Form Content',
                    'editorId' => 'completion_template_html',
                ])
            @endif

            @include('admin.listening.question-builders.completion.partials.bulk-import', [
                'group' => $group,
                'type' => $type,
            ])
        @endif
    </div>
</x-layouts.admin>
