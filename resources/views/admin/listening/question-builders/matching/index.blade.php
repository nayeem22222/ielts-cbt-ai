@php
    $type = $group->question_type;
    $optionKeyLabel = $type->usesRomanOptionKeys() ? 'Roman Key' : 'Option Key';
    $optionTextLabel = match ($type->value) {
        'matching_headings' => 'Heading Text',
        'matching_features', 'matching_people' => 'Name / Feature',
        'matching_sentence_endings' => 'Ending Text',
        default => 'Label (optional)',
    };
    $questionPromptLabel = match ($type->value) {
        'matching_headings' => 'Paragraph Reference',
        'matching_sentence_endings' => 'Sentence Beginning',
        default => 'Statement / Prompt',
    };
    $builderConfig = [
        'groupId' => $group->id,
        'optionSortableId' => 'matching-option-sortable',
        'questionSortableId' => 'matching-question-sortable',
        'reorderUrl' => route('admin.listening-question-groups.matching.reorder', $group),
    ];
@endphp

<x-layouts.admin
    :title="$group->title.' — Matching Builder'"
    :heading="$group->title"
    eyebrow="Matching Question Builder"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard')],
        ['label' => 'Listening Tests', 'href' => route('admin.listening.tests.index')],
        ['label' => $listeningTest->title, 'href' => route('admin.listening.tests.builder.index', ['listeningTest' => $listeningTest, 'section' => $section->id, 'question_group' => $group->id])],
        ['label' => 'Matching Questions'],
    ]"
>
    @push('head')
        @vite(['resources/js/reading-matching-builder.js'])
    @endpush

    <div x-data="readingMatchingBuilder(@js($builderConfig))" x-init="init()">
        @include('admin.listening.question-builders.matching.partials.header')

        <div class="mb-4 flex flex-wrap gap-2">
            <x-ui.button href="{{ route('admin.listening-question-groups.matching-questions.index', $group) }}">Edit Builder</x-ui.button>
            <x-ui.button href="{{ route('admin.listening-question-groups.matching-questions.index', ['group' => $group, 'preview' => 1]) }}" variant="outline">Preview</x-ui.button>
            <x-ui.button href="{{ route('admin.listening.tests.builder.index', ['listeningTest' => $listeningTest, 'section' => $section->id, 'question_group' => $group->id]) }}" variant="outline">Back to Group</x-ui.button>
        </div>

        @if ($showPreview)
            @include('admin.listening.question-builders.matching.partials.preview', [
                'type' => $type,
                'options' => $options,
                'questions' => $questions,
                'group' => $group,
                'test' => $listeningTest,
                'section' => $section,
                'renderer' => app(\App\Services\Exam\ReadingTestRendererService::class),
            ])
        @else
            @include('admin.listening.question-builders.partials.validation-errors')

            <div class="grid gap-6 xl:grid-cols-2">
                @include('admin.listening.question-builders.matching.partials.options-section', [
                    'group' => $group,
                    'options' => $options,
                    'optionKeyLabel' => $optionKeyLabel,
                    'optionTextLabel' => $optionTextLabel,
                ])

                @include('admin.listening.question-builders.matching.partials.questions-section', [
                    'group' => $group,
                    'questions' => $questions,
                    'options' => $options,
                    'questionPromptLabel' => $questionPromptLabel,
                    'type' => $type,
                ])
            </div>

            @include('admin.listening.question-builders.matching.partials.bulk-import', ['group' => $group, 'type' => $type])
        @endif
    </div>
</x-layouts.admin>
