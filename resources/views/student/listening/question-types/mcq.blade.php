@php $options = $group['options'] ?? $question['options'] ?? []; @endphp
<div class="space-y-2">
    @foreach ($options as $option)
        <label class="flex items-center gap-2 text-sm">
            <input type="radio" name="q_{{ $question['question_number'] }}" value="{{ $option['key'] ?? '' }}" class="listening-answer-input" data-question-id="{{ $question['id'] }}" @checked(collect($question['student_answer'] ?? [])->contains(fn ($a) => ($a['value'] ?? null) === ($option['key'] ?? null)))>
            <span>{{ $option['key'] ?? '' }}. {{ $option['text'] ?? '' }}</span>
        </label>
    @endforeach
</div>
