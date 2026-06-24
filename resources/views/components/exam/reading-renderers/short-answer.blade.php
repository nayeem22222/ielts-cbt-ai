<template x-if="group.renderer === 'short-answer'">
    <section class="rounded-lg border border-neutral-300 bg-white p-4">
        <div class="mb-4">
            <h3 class="text-base font-bold text-[#2D6A4F]" x-text="group.title"></h3>
            <p class="mt-1 text-sm leading-6 text-neutral-600" x-text="group.instruction"></p>
        </div>

        <div class="space-y-4">
            <template x-for="question in group.questions" :key="'short-question-'+question.id">
                <label :id="'question-'+question.id" class="block rounded border p-3 transition" :class="activeQuestionId === question.id ? 'border-[#2D6A4F] bg-emerald-50/60' : 'border-neutral-200 bg-white'">
                    <span class="mb-2 flex items-start justify-between gap-3 text-sm leading-6 text-neutral-800">
                        <span>
                            <span class="mr-2 font-bold" x-text="question.number"></span>
                            <span x-html="formatPrompt(question.prompt)"></span>
                        </span>
                        <button type="button" @click.stop="toggleFlag(question.id)" class="shrink-0 text-xs font-semibold" :class="flagged[question.id] ? 'text-amber-700' : 'text-neutral-400'" x-text="flagged[question.id] ? 'Flagged' : 'Flag'"></button>
                    </span>
                    <input
                        type="text"
                        class="w-full rounded border border-neutral-300 bg-white px-3 py-2 text-sm outline-none focus:border-[#2D6A4F]"
                        :placeholder="'Answer ' + question.number"
                        x-model="answers[question.id]"
                        @focus="selectQuestion(question.id)"
                        @input="queueAutosave()"
                    >
                </label>
            </template>
        </div>
    </section>
</template>
