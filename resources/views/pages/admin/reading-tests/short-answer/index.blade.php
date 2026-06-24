<x-layouts.admin
    :title="$group->title.' — Short Answer Builder'"
    :heading="$group->title"
    eyebrow="Short Answer Builder"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard')],
        ['label' => 'Reading Tests', 'href' => route('admin.reading-tests.index')],
        ['label' => $test->title, 'href' => route('admin.reading-tests.builder', ['readingTest' => $test, 'passage' => $passage->id, 'question_group' => $group->id])],
        ['label' => 'Short Answer Questions'],
    ]"
>
    @push('head')
        @vite(['resources/js/reading-short-answer-builder.js'])
    @endpush

    <div x-data="readingShortAnswerBuilder()" x-init="init()">
        @include('pages.admin.reading-tests.short-answer.partials.header')

        <div class="mb-4 flex flex-wrap gap-2">
            <x-ui.button href="{{ route('admin.reading-question-groups.short-answer-questions.edit', $group) }}">Edit Builder</x-ui.button>
            <x-ui.button href="{{ route('admin.reading-question-groups.short-answer-questions.preview', $group) }}" variant="outline">Preview</x-ui.button>
            <x-ui.button href="{{ route('admin.reading-tests.builder', ['readingTest' => $test, 'passage' => $passage->id, 'question_group' => $group->id]) }}" variant="outline">Back to Group</x-ui.button>
        </div>

        @if ($showPreview)
            @include('pages.admin.reading-tests.short-answer.partials.preview', [
                'group' => $group,
                'questions' => $questions,
                'settings' => $settings,
                'answerRules' => $answerRules,
            ])
        @else
            @include('pages.admin.reading-tests.short-answer.partials.add-question', [
                'group' => $group,
                'answerRules' => $answerRules,
                'settings' => $settings,
            ])

            @include('pages.admin.reading-tests.short-answer.partials.question-list', [
                'group' => $group,
                'questions' => $questions,
            ])
        @endif
    </div>
</x-layouts.admin>
