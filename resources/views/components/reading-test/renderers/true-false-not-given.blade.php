@php
    $choices = $type->objectiveAnswerChoices() ?? [];
@endphp

<div class="reading-test-objective-table overflow-x-auto">
    <table class="reading-test-table">
        <thead>
            <tr>
                <th>Question</th>
                @foreach ($choices as $choice)
                    <th class="w-28 text-center">{{ str_replace('_', ' ', $choice) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($questions as $question)
                <tr class="reading-test-question-row" data-question-number="{{ $question->question_number }}">
                    <td>
                        <div class="flex items-start gap-2">
                            <span class="font-semibold">{{ $question->question_number }}.</span>
                            <span class="flex-1">{{ $question->prompt }}</span>
                            <x-reading-test.flag-button :question="$question" />
                        </div>
                    </td>
                    @foreach ($choices as $choice)
                        <td class="text-center">
                            <input
                                type="radio"
                                name="q_{{ $question->question_number }}"
                                value="{{ $choice }}"
                                class="reading-test-input reading-test-radio"
                                data-test-id="{{ $test->id }}"
                                data-passage-id="{{ $passage->id }}"
                                data-group-id="{{ $group->id }}"
                                data-question-id="{{ $question->id }}"
                                data-question-number="{{ $question->question_number }}"
                                data-question-type="{{ $type->value }}"
                                aria-label="Question {{ $question->question_number }} {{ str_replace('_', ' ', $choice) }}"
                            />
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
