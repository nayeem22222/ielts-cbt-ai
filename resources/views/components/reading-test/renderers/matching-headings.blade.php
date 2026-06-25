@php
    use App\Support\Reading\ReadingGroupInteraction;

    $interactionMode = ReadingGroupInteraction::mode($group);
    $allowReuse = ReadingGroupInteraction::allowReuse($group);
    $romanKeys = $type->usesRomanOptionKeys();
@endphp

@if ($interactionMode === ReadingGroupInteraction::MODE_DRAG_DROP)
    <div
        class="reading-dnd-group reading-test-matching-headings-dnd"
        data-group-id="{{ $group->id }}"
        data-dnd-type="matching_headings"
        data-dnd-allow-reuse="{{ $allowReuse ? '1' : '0' }}"
    >
        @php
            $headingsMap = $questions->map(function ($q) use ($test, $passage, $group, $type) {
                return [
                    'test_id' => $test->id,
                    'passage_id' => $passage->id,
                    'group_id' => $group->id,
                    'question_id' => $q->id,
                    'question_number' => $q->question_number,
                    'question_type' => $type->value,
                    'paragraph_reference' => strtoupper(trim((string) ($q->paragraph_reference ?? $q->reference_paragraph ?? ''))),
                ];
            })->values();
        @endphp
        <div class="reading-dnd-headings-config hidden" aria-hidden="true"
            data-passage-id="{{ $passage->id }}"
            data-questions='@json($headingsMap)'
        >
            <template class="reading-dnd-passage-template">
                <div class="reading-dnd-passage-slot">
                    <div
                        class="reading-dnd-dropzone reading-dnd-dropzone--empty reading-dnd-dropzone--passage"
                        tabindex="0"
                        role="button"
                    >
                        <input type="hidden" class="reading-test-input reading-dnd-input" value="" />
                        <span class="reading-dnd-dropzone__paragraph-label font-semibold"></span>
                        <span class="reading-dnd-dropzone__placeholder">Drop heading here</span>
                        <span class="reading-dnd-dropzone__filled" hidden>
                            <span class="reading-dnd-dropzone__key"></span>
                            <span class="reading-dnd-dropzone__label"></span>
                            <button type="button" class="reading-dnd-dropzone__remove" aria-label="Remove heading">&times;</button>
                        </span>
                    </div>
                </div>
            </template>
        </div>

        <h4 class="reading-test-subheading">List of Headings</h4>
        <p class="mb-3 text-sm text-neutral-600">Drag a heading to the matching paragraph in the passage. You can also click a heading, then click a drop zone.</p>

        @include('components.reading-test.renderers.dnd.option-pool', [
            'options' => $options,
            'group' => $group,
            'romanKeys' => $romanKeys,
        ])

        <ul class="mt-4 space-y-2 border-t border-neutral-200 pt-3">
            @foreach ($questions as $question)
                <li class="reading-test-question-row flex flex-wrap items-center gap-2 text-sm" data-question-number="{{ $question->question_number }}">
                    <span class="font-semibold">{{ $question->question_number }}.</span>
                    <span>{{ $question->prompt }}</span>
                    <x-reading-test.report-question-button :question="$question" />
                </li>
            @endforeach
        </ul>
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
