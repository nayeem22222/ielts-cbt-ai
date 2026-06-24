<template x-if="group.renderer === 'sentence-completion'">
    <section class="rounded-lg border border-neutral-300 bg-white p-4">
        <div class="mb-4">
            <h3 class="text-base font-bold text-[#2D6A4F]" x-text="group.title"></h3>
            <p class="mt-1 text-sm leading-6 text-neutral-600" x-text="group.instruction"></p>
        </div>

        <div class="space-y-3">
            <template x-for="question in group.questions" :key="'completion-question-'+question.id">
                <label :id="'question-'+question.id" class="block rounded p-2 transition" :class="activeQuestionId === question.id ? 'bg-emerald-50 ring-1 ring-[#2D6A4F]' : ''">
                    <span class="mb-2 block text-sm leading-6 text-neutral-800">
                        <span class="mr-2 inline-flex h-7 min-w-7 items-center justify-center rounded bg-neutral-100 px-1 text-xs font-bold" x-text="question.number"></span>
                        <span x-html="formatPrompt(question.prompt)"></span>
                    </span>
                    <span class="flex items-center gap-2">
                        <input
                            type="text"
                            class="min-w-0 flex-1 rounded border border-neutral-300 bg-white px-3 py-2 text-sm outline-none focus:border-[#2D6A4F]"
                            :placeholder="'Answer ' + question.number"
                            x-model="answers[question.id]"
                            @focus="selectQuestion(question.id)"
                            @input="queueAutosave()"
                        >
                        <button type="button" @click.stop="toggleFlag(question.id)" class="text-xs font-semibold" :class="flagged[question.id] ? 'text-amber-700' : 'text-neutral-400'" x-text="flagged[question.id] ? 'Flagged' : 'Flag'"></button>
                    </span>
                </label>
            </template>
        </div>
    </section>
</template>
