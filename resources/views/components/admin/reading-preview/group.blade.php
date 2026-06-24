@props([
    'group',
    'showCorrectAnswers' => false,
    'showExplanations' => false,
    'answerRules' => [],
])

@php
    $type = $group->question_type;
    $questions = $group->questions;
    $settings = $group->settings ?? [];
@endphp

<div class="rounded-2xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-950">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-600">{{ $type?->label() }}</p>
            <h3 class="text-lg font-bold text-neutral-900 dark:text-white">{{ $group->title }}</h3>
            <p class="text-sm aa-muted">Questions {{ $group->question_range_label }}</p>
        </div>
        <x-ui.badge tone="blue">Q{{ $group->question_range_label }}</x-ui.badge>
    </div>

    @if ($group->instruction)
        <p class="mb-4 text-sm italic aa-muted">{{ $group->instruction }}</p>
    @endif

    @if ($type?->isMatchingBuilderType())
        @include('pages.admin.reading-tests.matching.partials.preview', [
            'type' => $type,
            'questions' => $questions,
            'options' => $group->groupOptions,
            'group' => $group,
        ])
    @elseif ($type?->isObjectiveBuilderType())
        @include('pages.admin.reading-tests.objective.partials.preview', [
            'type' => $type,
            'questions' => $questions,
            'group' => $group,
        ])
    @elseif ($type?->isCompletionBuilderType())
        @php
            $completionSettings = [
                'answer_rule' => $settings['answer_rule'] ?? 'one_word_only',
                'custom_answer_rule' => $settings['custom_answer_rule'] ?? null,
                'template_html' => $settings['template_html'] ?? '',
            ];
        @endphp
        @include('pages.admin.reading-tests.completion.partials.preview', [
            'group' => $group,
            'questions' => $questions,
            'settings' => $completionSettings,
            'answerRules' => $answerRules,
            'previewHtml' => \App\Support\Reading\CompletionPlaceholderParser::renderPreviewHtml($completionSettings['template_html']),
        ])
    @elseif ($type?->isDiagramBuilderType())
        @include('pages.admin.reading-tests.diagram.partials.preview', [
            'group' => $group,
            'questions' => $questions,
            'settings' => [
                'answer_rule' => $settings['answer_rule'] ?? 'one_word_only',
                'custom_answer_rule' => $settings['custom_answer_rule'] ?? null,
                'labels' => $settings['labels'] ?? [],
            ],
            'diagramImageUrl' => ! empty($settings['diagram_image'])
                ? route('admin.reading-question-groups.diagram-questions.image', $group)
                : null,
            'answerRules' => $answerRules,
        ])
    @elseif ($type?->isShortAnswerBuilderType())
        @include('pages.admin.reading-tests.short-answer.partials.preview', [
            'group' => $group,
            'questions' => $questions,
            'settings' => [
                'answer_rule' => $settings['answer_rule'] ?? 'three_words',
                'custom_answer_rule' => $settings['custom_answer_rule'] ?? null,
            ],
            'answerRules' => $answerRules,
        ])
    @else
        <x-ui.empty-state title="Unsupported preview type">No preview renderer is configured for this question type.</x-ui.empty-state>
    @endif

    @if ($showCorrectAnswers)
        <div class="mt-6 space-y-2 border-t border-neutral-200 pt-4 dark:border-neutral-700">
            <h4 class="text-sm font-semibold">Correct Answers</h4>
            @foreach ($questions as $question)
                @php
                    $correct = $question->correctAnswers->first();
                    $alternatives = \App\Support\Reading\CompletionAnswerPayload::alternatives($correct);
                @endphp
                <div class="rounded-lg border border-neutral-200 px-3 py-2 text-sm dark:border-neutral-700">
                    <span class="font-bold text-brand-700">Q{{ $question->question_number }}</span>
                    <span class="mx-2">→</span>
                    <span>{{ $correct?->answer ?: (is_array($correct?->answer_json) ? implode(', ', $correct->answer_json) : '—') }}</span>
                    @if ($alternatives !== [])
                        <span class="aa-muted"> (also: {{ implode(', ', $alternatives) }})</span>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if ($showExplanations)
        <div class="mt-6 space-y-2 border-t border-neutral-200 pt-4 dark:border-neutral-700">
            <h4 class="text-sm font-semibold">Explanations</h4>
            @foreach ($questions as $question)
                @if ($question->explanation)
                    <div class="rounded-lg border border-neutral-200 px-3 py-2 text-sm dark:border-neutral-700">
                        <span class="font-bold text-brand-700">Q{{ $question->question_number }}</span>
                        <p class="mt-1 aa-muted">{{ $question->explanation }}</p>
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</div>
