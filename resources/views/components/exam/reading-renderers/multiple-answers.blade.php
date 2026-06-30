<template x-if="group.renderer === 'multiple-answers'">
    <section class="rounded-lg border border-neutral-300 bg-white p-4">
        <div class="mb-4">
            <h3 class="text-base font-bold text-[#2D6A4F]" x-text="group.title"></h3>
            <p class="mt-1 text-sm leading-6 text-neutral-600" x-text="group.instruction"></p>
        </div>

        <div class="space-y-5">
            <template x-for="question in group.questions" :key="'multi-question-'+question.id">
                <div :id="'question-'+question.id" class="rounded border p-4 transition" :class="activeQuestionId === question.id ? 'border-[#2D6A4F] bg-emerald-50/60' : 'border-neutral-200 bg-white'">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <p class="text-sm font-medium leading-6 text-neutral-800">
                            <span class="mr-2 font-bold" x-text="question.number"></span>
                            <span x-html="formatPrompt(question.prompt)"></span>
                        </p>
                        <button type="button" @click.stop="toggleFlag(question.id)" class="text-xs font-semibold" :class="flagged[question.id] ? 'text-amber-700' : 'text-neutral-400'" x-text="flagged[question.id] ? 'Flagged' : 'Flag'"></button>
                    </div>

                    <div class="space-y-2">
                        <template x-for="option in question.options" :key="'multi-option-'+question.id+'-'+option.label">
                            <label class="flex cursor-pointer items-start gap-3 rounded border px-3 py-2 text-sm transition" :class="isMultiSelected(question.id, option.text) ? 'border-[#2D6A4F] bg-[#2D6A4F]/10' : 'border-neutral-200 bg-white hover:border-neutral-300'">
                                <input
                                    type="checkbox"
                                    class="mt-1 accent-[#2D6A4F]"
                                    :checked="isMultiSelected(question.id, option.text)"
                                    :disabled="isMultiAnswerOptionDisabled(question.id, option.text)"
                                    @change.stop="selectQuestion(question.id); toggleMultiAnswer(question.id, option.text)"
                                >
                                <span><strong x-text="option.label + '. '"></strong><span x-text="option.text"></span></span>
                            </label>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </section>
</template>
