@php $options = $group['options'] ?? $question['options'] ?? []; $saved = collect($question['student_answer'] ?? [])->pluck('value')->all(); @endphp
<div class="space-y-2">
    @foreach ($options as $option)
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="q_{{ $question['question_number'] }}[]" value="{{ $option['key'] ?? '' }}" class="listening-answer-input" data-question-id="{{ $question['id'] }}" @checked(in_array($option['key'] ?? '', $saved, true))>
            <span>{{ $option['key'] ?? '' }}. {{ $option['text'] ?? '' }}</span>
        </label>
    @endforeach
</div>
