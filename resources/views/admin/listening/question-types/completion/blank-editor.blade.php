@php
    $existing = old('correct_answer', $question->correct_answer ?? []);
    if (is_string($existing)) {
        $decoded = json_decode($existing, true);
        $existing = is_array($decoded) ? $decoded : [];
    }
    $simpleValue = '';
    if (is_array($existing) && isset($existing[0])) {
        $first = $existing[0];
        $simpleValue = is_array($first) ? (string) ($first['value'] ?? '') : (string) $first;
    }
    $wordLimit = $question->word_limit ?? ($group->settings['word_limit'] ?? null);
@endphp
<div
    class="rounded-xl border border-neutral-200 bg-neutral-50/50 p-4 dark:border-neutral-800 dark:bg-neutral-900/40"
    x-data="{
        answer: @js($simpleValue),
        jsonValue() {
            if (! this.answer.trim()) {
                return '';
            }
            return JSON.stringify([{ value: this.answer.trim(), type: 'text' }]);
        },
    }"
>
    <label class="block">
        <span class="mb-2 block text-sm font-medium text-neutral-800 dark:text-neutral-200">Correct answer</span>
        <input
            type="text"
            class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm dark:border-neutral-800 dark:bg-neutral-900"
            x-model="answer"
            placeholder="e.g. hotel"
        >
    </label>
    <p class="mt-2 text-xs aa-muted">
        Official answer for this blank.
        @if ($wordLimit)
            Word limit: {{ $wordLimit }}.
        @endif
        Use the group template for where this answer appears.
    </p>
    <input type="hidden" name="correct_answer" :value="jsonValue()">
</div>
