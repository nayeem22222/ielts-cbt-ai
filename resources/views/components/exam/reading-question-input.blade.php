{{-- ProQyz-style per-type answer control (Alpine scope: question from x-for) --}}
<template x-if="question.ui_pattern === 'letter_picker'">
    <div class="mt-3 flex flex-wrap gap-2">
        <template x-for="option in question.options" :key="option.label">
            <button
                type="button"
                @click="setAnswer(question.id, option.label)"
                class="grid h-9 min-w-9 place-items-center rounded-lg border px-2 text-sm font-bold transition"
                :class="answers[question.id] === option.label ? 'border-brand-500 bg-brand-500 text-white' : 'border-neutral-300 bg-white text-neutral-800 hover:border-brand-300 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100'"
                x-text="option.label"
            ></button>
        </template>
    </div>
</template>

<template x-if="question.ui_pattern === 'binary_triple'">
    <div class="mt-3 flex flex-wrap gap-2">
        <template x-for="option in (question.options.length ? question.options : defaultBinaryOptions(question))" :key="option.label">
            <button
                type="button"
                @click="setAnswer(question.id, option.text || option.label)"
                class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                :class="answers[question.id] === (option.text || option.label) ? 'border-brand-500 bg-brand-500 text-white' : 'border-neutral-300 bg-white text-neutral-800 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100'"
                x-text="option.text || option.label"
            ></button>
        </template>
    </div>
</template>

<template x-if="question.ui_pattern === 'mcq_single'">
    <div class="mt-3 space-y-2">
        <template x-for="option in question.options" :key="option.label">
            <button
                type="button"
                @click="setAnswer(question.id, option.text)"
                class="flex w-full items-start gap-3 rounded-xl border p-3 text-left text-sm transition"
                :class="answers[question.id] === option.text ? 'border-brand-500 bg-brand-50 dark:bg-brand-500/10' : 'border-neutral-200 dark:border-neutral-800'"
            >
                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-neutral-100 text-xs font-bold dark:bg-neutral-800" x-text="option.label"></span>
                <span x-text="option.text"></span>
            </button>
        </template>
    </div>
</template>

<template x-if="question.ui_pattern === 'mcq_multiple'">
    <div class="mt-3 space-y-2">
        <template x-for="option in question.options" :key="option.label">
            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-neutral-200 p-3 text-sm dark:border-neutral-800">
                <input
                    type="checkbox"
                    class="mt-1"
                    :checked="isMultiSelected(question.id, option.text)"
                    @change="toggleMultiAnswer(question.id, option.text)"
                >
                <span><strong x-text="option.label + '.'"></strong> <span x-text="option.text"></span></span>
            </label>
        </template>
    </div>
</template>

<template x-if="question.ui_pattern === 'text_input'">
    <div class="mt-3 flex flex-wrap items-center gap-2">
        <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-brand-50 px-2 text-sm font-bold text-brand-700 dark:bg-brand-500/10 dark:text-brand-200" x-text="question.number"></span>
        <input
            type="text"
            class="min-w-[12rem] flex-1 rounded-xl border border-neutral-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-brand-400 dark:border-neutral-800 dark:bg-neutral-950"
            :placeholder="'Answer for question ' + question.number"
            x-model="answers[question.id]"
        >
    </div>
</template>
