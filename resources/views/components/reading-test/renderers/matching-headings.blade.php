@php
    use App\Support\Reading\ReadingGroupInteraction;

    $interactionMode = ReadingGroupInteraction::mode($group);
    $allowReuse = ReadingGroupInteraction::allowReuse($group);
    $romanKeys = $type->usesRomanOptionKeys();

@endphp

@if ($interactionMode === ReadingGroupInteraction::MODE_DRAG_DROP)
    @php
        $headingsMap = $questions->values()->map(function ($q) use ($test, $passage, $group, $type) {
            $ref = strtoupper(trim((string) ($q->paragraph_reference ?? $q->reference_paragraph ?? '')));
            if ($ref === '' && preg_match('/\b([A-Z])\b/', (string) $q->prompt, $m)) {
                $ref = $m[1];
            }

            return [
                'test_id' => $test->id,
                'passage_id' => $passage->id,
                'group_id' => $group->id,
                'question_id' => $q->id,
                'question_number' => $q->question_number,
                'question_type' => $type->value,
                'paragraph_reference' => $ref,
                'option_labels' => $group->groupOptions->mapWithKeys(fn ($o) => [$o->option_key => $o->option_label])->all(),
            ];
        })->values();
    @endphp

    <div
        class="reading-dnd-group reading-test-matching-headings-dnd"
        data-group-id="{{ $group->id }}"
        data-dnd-type="matching_headings"
        data-dnd-allow-reuse="0"
        data-dnd-layout="ielts-passage"
    >
        {{-- Passage drop-zone injection config (left panel) --}}
        <div
            class="reading-dnd-headings-config hidden"
            aria-hidden="true"
            data-passage-id="{{ $passage->id }}"
            data-group-id="{{ $group->id }}"
            data-option-labels='@json($options->mapWithKeys(fn ($o) => [$o->option_key => $o->option_label])->all())'
            data-questions='@json($headingsMap)'
        >
            <template class="reading-dnd-passage-template">
                <div class="reading-mh-passage-slot">
                    <div
                        class="reading-dnd-dropzone reading-dnd-dropzone--empty reading-dnd-dropzone--passage reading-mh-passage-dropzone"
                        tabindex="0"
                        role="button"
                    >
                        <input type="hidden" class="reading-test-input reading-dnd-input" value="" />
                        <span class="reading-mh-dropzone__badge"></span>
                        <span class="reading-dnd-dropzone__placeholder reading-mh-dropzone__placeholder">Drop heading here</span>
                        <span class="reading-dnd-dropzone__filled reading-mh-dropzone__filled" hidden>
                            <span class="reading-dnd-dropzone__key reading-mh-dropzone__key"></span>
                            <span class="reading-dnd-dropzone__label reading-mh-dropzone__label"></span>
                            <button type="button" class="reading-dnd-dropzone__remove reading-mh-dropzone__remove" aria-label="Remove heading" title="Remove heading">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </span>
                    </div>
                </div>
            </template>
        </div>

        {{-- Right panel: sticky heading bank --}}
        <aside class="reading-mh-panel">
            @if ($group->title)
                <h3 class="reading-mh-panel__title">{{ $group->title }}</h3>
            @endif

            @if ($group->instruction)
                <p class="reading-mh-panel__instruction">{{ $group->instruction }}</p>
            @endif

            <h4 class="reading-mh-panel__subtitle">List of Headings</h4>
            <p class="reading-mh-panel__hint">Drag a heading to the matching paragraph in the passage.</p>

            @include('components.reading-test.renderers.dnd.matching-headings-pool', [
                'options' => $options,
                'group' => $group,
                'romanKeys' => $romanKeys,
            ])
        </aside>
    </div>
@else
    <div class="reading-test-matching-headings grid gap-6 lg:grid-cols-2">
        <div>
            <h4 class="reading-test-subheading">List of Headings</h4>
            <ul class="reading-test-option-list">
                @foreach ($options as $option)
                    <li>
                        <span class="font-semibold">{{ $option->option_key }}.</span>
                        {{ $option->option_label }}
                    </li>
                @endforeach
            </ul>
        </div>
        <div>
            <h4 class="reading-test-subheading">Paragraphs</h4>
            <ul class="space-y-3">
                @foreach ($questions as $question)
                    <li class="reading-test-question-row flex flex-wrap items-center gap-3 rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2" data-question-number="{{ $question->question_number }}">
                        <span class="font-semibold">{{ $question->question_number }}.</span>
                        <span class="flex-1 text-sm">{{ $question->prompt }}</span>
                        <select
                            class="reading-test-input reading-test-select min-w-[8rem]"
                            data-test-id="{{ $test->id }}"
                            data-passage-id="{{ $passage->id }}"
                            data-group-id="{{ $group->id }}"
                            data-question-id="{{ $question->id }}"
                            data-question-number="{{ $question->question_number }}"
                            data-question-type="{{ $type->value }}"
                        >
                            <option value="">—</option>
                            @foreach ($options as $option)
                                <option value="{{ $option->option_key }}">{{ $option->option_key }}</option>
                            @endforeach
                        </select>
                        <x-reading-test.report-question-button :question="$question" />
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
