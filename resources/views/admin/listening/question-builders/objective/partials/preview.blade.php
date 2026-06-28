<x-ui.card title="Admin Preview — {{ $type->label() }}">
  <div class="space-y-4">
    @foreach ($questions->filter(fn ($q) => $q->question_number > 0) as $question)
      @php
        $answer = $question->correctAnswers->first();
        $single = $answer?->answer;
        $multiple = $answer?->answer_json ?? [];
      @endphp
      <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
        <p class="font-semibold">Question {{ $question->question_number }}</p>
        <p class="mt-2 text-sm">{{ $question->prompt }}</p>

        @if (in_array($type->value, ['true_false_not_given', 'yes_no_not_given'], true))
          <div class="mt-3 flex flex-wrap gap-4">
            @foreach ($type->objectiveAnswerChoices() ?? [] as $choice)
              <label class="inline-flex items-center gap-2 text-sm">
                <input type="radio" disabled @checked($single === $choice)>
                <span>{{ str_replace('_', ' ', $choice) }}</span>
              </label>
            @endforeach
          </div>
        @elseif ($type->value === 'mcq')
          <div class="mt-3 space-y-2">
            @foreach ($question->options as $option)
              <label class="flex items-center gap-2 text-sm">
                <input type="radio" disabled @checked($single === $option->option_key)>
                <span><strong>{{ $option->option_key }}.</strong> {{ $option->option_label }}</span>
              </label>
            @endforeach
          </div>
        @elseif ($type->value === 'multiple_answer')
          <div class="mt-3 space-y-2">
            @foreach ($question->options as $option)
              <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" disabled @checked(in_array($option->option_key, $multiple, true))>
                <span><strong>{{ $option->option_key }}.</strong> {{ $option->option_label }}</span>
              </label>
            @endforeach
          </div>
        @endif
      </div>
    @endforeach
  </div>
</x-ui.card>
