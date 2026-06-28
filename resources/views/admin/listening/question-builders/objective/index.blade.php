<x-layouts.admin
    :title="$group->title.' — Objective Builder'"
    :heading="$group->title"
    eyebrow="Objective Question Builder"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard')],
        ['label' => 'Listening Tests', 'href' => route('admin.listening.tests.index')],
        ['label' => $listeningTest->title, 'href' => route('admin.listening.tests.builder.index', ['listeningTest' => $listeningTest, 'section' => $section->id, 'question_group' => $group->id])],
        ['label' => 'Objective Questions'],
    ]"
>
    @push('head')
        @vite(['resources/js/reading-objective-builder.js'])
    @endpush

    <div x-data="readingObjectiveBuilder()" x-init="init()">
        @include('admin.listening.question-builders.objective.partials.header')

        <div class="mb-4 flex flex-wrap gap-2">
            <x-ui.button href="{{ route('admin.listening-question-groups.objective-questions.index', $group) }}">Edit Builder</x-ui.button>
            <x-ui.button href="{{ route('admin.listening-question-groups.objective-questions.index', ['group' => $group, 'preview' => 1]) }}" variant="outline">Preview</x-ui.button>
            <x-ui.button href="{{ route('admin.listening.tests.builder.index', ['listeningTest' => $listeningTest, 'section' => $section->id, 'question_group' => $group->id]) }}" variant="outline">Back to Group</x-ui.button>
        </div>

        @if ($showPreview)
            @include('admin.listening.question-builders.objective.partials.preview', [
                'type' => $type,
                'questions' => $questions,
                'group' => $group,
            ])
        @else
            @if ($type->value === 'true_false_not_given')
                @include('admin.listening.question-builders.objective.partials.binary-builder', [
                    'group' => $group,
                    'questions' => $questions,
                    'answerChoices' => $answerChoices,
                    'statementLabel' => 'Statement',
                ])
            @elseif ($type->value === 'yes_no_not_given')
                @include('admin.listening.question-builders.objective.partials.binary-builder', [
                    'group' => $group,
                    'questions' => $questions,
                    'answerChoices' => $answerChoices,
                    'statementLabel' => 'Statement',
                ])
            @elseif ($type->value === 'mcq')
                @include('admin.listening.question-builders.objective.partials.mcq-single-builder', [
                    'group' => $group,
                    'questions' => $questions,
                ])
            @elseif ($type->value === 'multiple_answer')
                @include('admin.listening.question-builders.objective.partials.mcq-multiple-builder', [
                    'group' => $group,
                    'questions' => $questions,
                ])
            @endif

            @include('admin.listening.question-builders.objective.partials.bulk-import', ['group' => $group, 'type' => $type])
        @endif
    </div>
</x-layouts.admin>
