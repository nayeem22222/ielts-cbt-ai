@props(['group', 'answerRules', 'selectedRule' => null, 'customRule' => null])

<div>
    <label for="answer_rule" class="block text-sm font-medium text-neutral-700 dark:text-neutral-200">Answer Rule</label>
    <select
        id="answer_rule"
        name="answer_rule"
        required
        x-model="answerRule"
        class="mt-1 w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
    >
        @foreach ($answerRules as $rule)
            <option value="{{ $rule->value }}" @selected(($selectedRule ?? '') === $rule->value)>{{ $rule->label() }}</option>
        @endforeach
    </select>
</div>

<div x-show="answerRule === 'custom'" x-cloak>
    <label for="custom_answer_rule" class="block text-sm font-medium text-neutral-700 dark:text-neutral-200">Custom Rule</label>
    <input
        id="custom_answer_rule"
        name="custom_answer_rule"
        type="text"
        x-model="customAnswerRule"
        value="{{ old('custom_answer_rule', $customRule) }}"
        class="mt-1 w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
        placeholder="e.g. NO MORE THAN TWO WORDS AND/OR A NUMBER"
    >
</div>
