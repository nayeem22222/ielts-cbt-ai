@php
    $groupType = $group->question_type?->value ?? 'form_completion';
    $options = is_array($group->options) ? $group->options : [];
    $existing = old('correct_answer', $question->correct_answer ?? []);
    if (is_string($existing)) {
        $decoded = json_decode($existing, true);
        $existing = is_array($decoded) ? $decoded : [];
    }
    $selectedLetter = '';
    if (is_array($existing) && isset($existing[0]['value'])) {
        $selectedLetter = (string) $existing[0]['value'];
    }
    $isMcq = in_array($groupType, ['mcq', 'multiple_answer'], true);
    $isCompletion = str_contains($groupType, 'completion') || $groupType === 'short_answer';
@endphp
<div class="md:col-span-2 space-y-3">
    @if ($isMcq)
        <div
            x-data="{
                letter: @js($selectedLetter),
                jsonValue() {
                    if (! this.letter) {
                        return '';
                    }
                    return JSON.stringify([{ value: this.letter, type: 'letter' }]);
                },
            }"
        >
            <label class="block">
                <span class="mb-2 block text-sm font-medium text-neutral-800 dark:text-neutral-200">Correct answer</span>
                <select
                    class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm dark:border-neutral-800 dark:bg-neutral-900"
                    x-model="letter"
                >
                    <option value="">Select option…</option>
                    @foreach ($options as $option)
                        @if (is_array($option) && isset($option['key']))
                            <option value="{{ $option['key'] }}">{{ $option['key'] }} — {{ $option['text'] ?? '' }}</option>
                        @endif
                    @endforeach
                </select>
            </label>
            <p class="mt-2 text-xs aa-muted">Options come from the question group. Edit group options if choices are missing.</p>
            <input type="hidden" name="correct_answer" :value="jsonValue()">
        </div>
    @elseif ($isCompletion)
        @include('admin.listening.question-types.completion.blank-editor')
    @elseif ($groupType === 'matching')
        <div class="rounded-xl border border-amber-100 bg-amber-50/60 p-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/20">
            Matching answer format: <code>{"item_key":"1","value":"A"}</code>. Use the advanced JSON editor below if needed.
        </div>
    @elseif (in_array($groupType, ['map_labelling', 'plan_labelling', 'diagram_labelling'], true))
        <div class="rounded-xl border border-amber-100 bg-amber-50/60 p-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/20">
            Label answer format: <code>{"label":"1","value":"A"}</code>. Use the advanced JSON editor below if needed.
        </div>
    @endif
</div>
