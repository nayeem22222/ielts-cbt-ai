<div class="reading-test-mcq space-y-5">
    @php
        $maxSelections = null;
        $instructionText = trim((string) ($group->instruction ?? ''));

        if (preg_match('/\b(?:choose|select)\s+(one|two|three|four|five|six|\d+)\b/i', $instructionText, $matches) === 1) {
            $wordMap = ['one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5, 'six' => 6];
            $token = strtolower($matches[1]);
            $maxSelections = $wordMap[$token] ?? (((int) $token) > 0 ? (int) $token : null);
        }

        $maxSelections = max(1, (int) ($maxSelections ?? 2));
    @endphp
    @foreach ($questions as $question)
        <div class="reading-test-question-row rounded-lg border border-neutral-200 bg-neutral-50 p-4" data-question-number="{{ $question->question_number }}">
            <div class="flex items-start justify-between gap-2">
                <p class="text-sm font-semibold text-brand-700">Question {{ $question->question_number }}</p>
                <div class="flex items-center gap-1">
                    <x-reading-test.flag-button :question="$question" />
                    <x-reading-test.report-question-button :question="$question" />
                </div>
            </div>
            <p class="mt-2 text-sm">{{ $question->prompt }}</p>
            <div class="mt-3 space-y-2" data-required-selections="{{ $maxSelections }}">
                @foreach ($question->options as $option)
                    <label class="reading-test-mcq-option flex cursor-pointer items-start gap-2 text-sm">
                        <input
                            type="checkbox"
                            name="q_{{ $question->question_number }}[]"
                            value="{{ $option->option_key }}"
                            class="reading-test-input reading-test-checkbox mt-0.5"
                            data-test-id="{{ $test->id }}"
                            data-passage-id="{{ $passage->id }}"
                            data-group-id="{{ $group->id }}"
                            data-question-id="{{ $question->id }}"
                            data-question-number="{{ $question->question_number }}"
                            data-question-type="{{ $type->value }}"
                        />
                        <span><strong>{{ $option->option_key }}.</strong> {{ $option->option_label }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
