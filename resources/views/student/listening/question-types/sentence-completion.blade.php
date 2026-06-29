@php $saved = collect($question['student_answer'] ?? [])->first()['value'] ?? ''; @endphp
<input type="text" class="listening-answer-input w-full rounded-xl border px-3 py-2 text-sm" data-question-id="{{ $question['id'] }}" value="{{ $saved }}" maxlength="120">
