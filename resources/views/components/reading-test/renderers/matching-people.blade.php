@php
    use App\Support\Reading\ReadingGroupInteraction;

    $interactionMode = ReadingGroupInteraction::mode($group);
    $allowReuse = ReadingGroupInteraction::allowReuse($group);
@endphp

@if ($interactionMode === ReadingGroupInteraction::MODE_DRAG_DROP)
    <div
        class="reading-dnd-group reading-test-matching-people-dnd space-y-4"
        data-group-id="{{ $group->id }}"
        data-dnd-type="matching_people"
        data-dnd-allow-reuse="{{ $allowReuse ? '1' : '0' }}"
    >
        <div>
            <h4 class="reading-test-subheading">People</h4>
            @include('components.reading-test.renderers.dnd.option-pool', [
                'options' => $options,
                'group' => $group,
                'romanKeys' => false,
            ])
        </div>
        <div>
            <h4 class="reading-test-subheading">Statements</h4>
            <ul class="space-y-3">
                @foreach ($questions as $question)
                    <li class="reading-test-question-row flex flex-wrap items-center gap-3 rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2" data-question-number="{{ $question->question_number }}">
                        <span class="font-semibold">{{ $question->question_number }}.</span>
                        <span class="flex-1 text-sm">{{ $question->prompt }}</span>
                        @include('components.reading-test.renderers.dnd.dropzone', [
                            'test' => $test,
                            'passage' => $passage,
                            'group' => $group,
                            'question' => $question,
                            'type' => $type,
                        ])
                        <x-reading-test.report-question-button :question="$question" />
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@else
    <div class="reading-test-matching-people grid gap-6 lg:grid-cols-2">
        <div>
            <h4 class="reading-test-subheading">People</h4>
            <ul class="reading-test-option-list">
                @foreach ($options as $option)
                    <li>
                        <span class="font-semibold">{{ $option->option_key }}.</span>
                        {{ $option->option_label ?: '—' }}
                    </li>
                @endforeach
            </ul>
        </div>
        <div>
            <h4 class="reading-test-subheading">Statements</h4>
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
