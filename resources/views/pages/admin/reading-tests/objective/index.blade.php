<x-layouts.admin
    :title="$group->title.' — Objective Builder'"
    :heading="$group->title"
    eyebrow="Objective Question Builder"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard')],
        ['label' => 'Reading Tests', 'href' => route('admin.reading-tests.index')],
        ['label' => $test->title, 'href' => route('admin.reading-tests.builder', ['readingTest' => $test, 'passage' => $passage->id, 'question_group' => $group->id])],
        ['label' => 'Objective Questions'],
    ]"
>
    @push('head')
        @vite(['resources/js/reading-objective-builder.js'])
    @endpush

    <div x-data="readingObjectiveBuilder()" x-init="init()">
        @include('pages.admin.reading-tests.objective.partials.header')

        <div class="mb-4 flex flex-wrap gap-2">
            <x-ui.button href="{{ route('admin.reading-question-groups.objective-questions.index', $group) }}">Edit Builder</x-ui.button>
            <x-ui.button href="{{ route('admin.reading-question-groups.objective-questions.index', ['group' => $group, 'preview' => 1]) }}" variant="outline">Preview</x-ui.button>
            <x-ui.button href="{{ route('admin.reading-tests.builder', ['readingTest' => $test, 'passage' => $passage->id, 'question_group' => $group->id]) }}" variant="outline">Back to Group</x-ui.button>
        </div>

        @if ($showPreview)
            @include('pages.admin.reading-tests.objective.partials.preview', [
                'type' => $type,
                'questions' => $questions,
                'group' => $group,
            ])
        @else
            @if ($type->value === 'true_false_not_given')
                @include('pages.admin.reading-tests.objective.partials.binary-builder', [
                    'group' => $group,
                    'questions' => $questions,
                    'answerChoices' => $answerChoices,
                    'statementLabel' => 'Statement',
                ])
            @elseif ($type->value === 'yes_no_not_given')
                @include('pages.admin.reading-tests.objective.partials.binary-builder', [
                    'group' => $group,
                    'questions' => $questions,
                    'answerChoices' => $answerChoices,
                    'statementLabel' => 'Statement',
                ])
            @elseif ($type->value === 'multiple_choice_single')
                @include('pages.admin.reading-tests.objective.partials.mcq-single-builder', [
                    'group' => $group,
                    'questions' => $questions,
                ])
            @elseif ($type->value === 'multiple_choice_multiple')
                @include('pages.admin.reading-tests.objective.partials.mcq-multiple-builder', [
                    'group' => $group,
                    'questions' => $questions,
                ])
            @endif

            @include('pages.admin.reading-tests.objective.partials.bulk-import', ['group' => $group, 'type' => $type])
        @endif
    </div>
</x-layouts.admin>
