<div class="reading-test-matching-grid overflow-x-auto">
    <table class="reading-test-table">
        <thead>
            <tr>
                <th class="w-12">#</th>
                <th>Statement</th>
                @foreach ($options as $option)
                    <th class="w-14 text-center">{{ $option->option_key }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($questions as $question)
                <tr data-question-number="{{ $question->question_number }}" class="reading-test-question-row">
                    <td class="font-semibold">{{ $question->question_number }}</td>
                    <td>{{ $question->prompt }}</td>
                    @foreach ($options as $option)
                        <td class="text-center">
                            <input
                                type="radio"
                                name="q_{{ $question->question_number }}"
                                value="{{ $option->option_key }}"
                                class="reading-test-input reading-test-radio"
                                data-test-id="{{ $test->id }}"
                                data-passage-id="{{ $passage->id }}"
                                data-group-id="{{ $group->id }}"
                                data-question-id="{{ $question->id }}"
                                data-question-number="{{ $question->question_number }}"
                                data-question-type="{{ $type->value }}"
                                aria-label="Question {{ $question->question_number }} option {{ $option->option_key }}"
                            />
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
