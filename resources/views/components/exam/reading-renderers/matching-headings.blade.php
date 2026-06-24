<template x-if="group.renderer === 'matching-headings'">
    <section class="rounded-lg border border-neutral-300 bg-white">
        <div class="border-b border-neutral-200 px-4 py-3">
            <h3 class="text-base font-bold text-[#2D6A4F]" x-text="group.title"></h3>
            <p class="mt-1 text-sm leading-6 text-neutral-600" x-text="group.instruction"></p>
        </div>

        <div class="grid gap-4 p-4 xl:grid-cols-[16rem_1fr]">
            <aside class="rounded border border-neutral-200 bg-neutral-50 p-3">
                <p class="mb-2 text-xs font-bold uppercase tracking-wide text-neutral-500">List of Headings</p>
                <div class="space-y-2">
                    <template x-for="option in group.options" :key="'heading-option-'+option.label">
                        <div class="flex gap-2 text-sm leading-5">
                            <span class="font-bold text-neutral-700" x-text="option.label"></span>
                            <span class="text-neutral-700" x-text="option.text"></span>
                        </div>
                    </template>
                </div>
            </aside>

            <div class="space-y-3">
                <template x-for="question in group.questions" :key="'heading-question-'+question.id">
                    <div :id="'question-'+question.id" class="rounded border p-3 transition" :class="activeQuestionId === question.id ? 'border-[#2D6A4F] bg-emerald-50/60' : 'border-neutral-200 bg-white'">
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <p class="text-sm leading-6 text-neutral-800">
                                <span class="mr-2 font-bold" x-text="question.number"></span>
                                <span x-html="formatPrompt(question.prompt)"></span>
                            </p>
                            <button type="button" @click.stop="toggleFlag(question.id)" class="text-xs font-semibold" :class="flagged[question.id] ? 'text-amber-700' : 'text-neutral-400'" x-text="flagged[question.id] ? 'Flagged' : 'Flag'"></button>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="option in group.options" :key="'heading-pick-'+question.id+'-'+option.label">
                                <button
                                    type="button"
                                    @click.stop="selectQuestion(question.id); setAnswer(question.id, option.label)"
                                    class="min-w-9 rounded border px-3 py-1.5 text-sm font-bold"
                                    :class="answers[question.id] === option.label ? 'border-[#2D6A4F] bg-[#2D6A4F] text-white' : 'border-neutral-300 bg-white text-neutral-700'"
                                    x-text="option.label"
                                ></button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </section>
</template>
