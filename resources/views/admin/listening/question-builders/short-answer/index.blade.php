<x-layouts.admin
    :title="$group->title.' — Short Answer Builder'"
    :heading="$group->title"
    eyebrow="Short Answer Builder"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard')],
        ['label' => 'Listening Tests', 'href' => route('admin.listening.tests.index')],
        ['label' => $listeningTest->title, 'href' => route('admin.listening.tests.builder.index', ['listeningTest' => $listeningTest, 'section' => $section->id, 'question_group' => $group->id])],
        ['label' => 'Short Answer Questions'],
    ]"
>
    @push('head')
        @vite(['resources/js/reading-short-answer-builder.js'])
    @endpush

    <div x-data="readingShortAnswerBuilder()" x-init="init()">
        @include('admin.listening.question-builders.short-answer.partials.header')

        <div class="mb-4 flex flex-wrap gap-2">
            <x-ui.button href="{{ route('admin.listening-question-groups.short-answer-questions.edit', $group) }}">Edit Builder</x-ui.button>
            <x-ui.button href="{{ route('admin.listening-question-groups.short-answer-questions.preview', $group) }}" variant="outline">Preview</x-ui.button>
            <x-ui.button href="{{ route('admin.listening.tests.builder.index', ['listeningTest' => $listeningTest, 'section' => $section->id, 'question_group' => $group->id]) }}" variant="outline">Back to Group</x-ui.button>
        </div>

        @if ($showPreview)
            @include('admin.listening.question-builders.short-answer.partials.preview', [
                'group' => $group,
                'questions' => $questions,
                'settings' => $settings,
                'answerRules' => $answerRules,
            ])
        @else
            @include('admin.listening.question-builders.short-answer.partials.add-question', [
                'group' => $group,
                'answerRules' => $answerRules,
                'settings' => $settings,
            ])

            @include('admin.listening.question-builders.short-answer.partials.question-list', [
                'group' => $group,
                'questions' => $questions,
            ])
        @endif
    </div>
</x-layouts.admin>
