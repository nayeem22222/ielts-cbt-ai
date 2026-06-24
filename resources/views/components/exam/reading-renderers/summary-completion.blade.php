<template x-if="group.renderer === 'summary-completion'">
    <section class="rounded-lg border border-neutral-300 bg-white p-4">
        <div class="mb-4">
            <h3 class="text-base font-bold text-[#2D6A4F]" x-text="group.title"></h3>
            <p class="mt-1 text-sm leading-6 text-neutral-600" x-text="group.instruction"></p>
        </div>

        <div class="mb-4 rounded border border-neutral-200 bg-neutral-50 p-3" x-show="group.options.length">
            <p class="mb-2 text-xs font-bold uppercase tracking-wide text-neutral-500">Word Bank</p>
            <div class="flex flex-wrap gap-2">
                <template x-for="option in group.options" :key="'summary-bank-'+option.label">
                    <span class="rounded border border-neutral-300 bg-white px-3 py-1.5 text-xs font-semibold">
                        <span x-text="option.label + '. '"></span><span x-text="option.text"></span>
                    </span>
                </template>
            </div>
        </div>

        <div class="space-y-3 text-sm leading-8">
            <template x-for="question in group.questions" :key="'summary-question-'+question.id">
                <p :id="'question-'+question.id" class="rounded px-2 py-1 transition" :class="activeQuestionId === question.id ? 'bg-emerald-50 ring-1 ring-[#2D6A4F]' : ''">
                    <span class="mr-2 inline-flex h-7 min-w-7 items-center justify-center rounded border border-neutral-300 bg-white px-1 text-xs font-bold" x-text="question.number"></span>
                    <span x-html="formatPrompt(question.prompt)"></span>
                    <template x-if="group.options.length">
                        <select
                            class="mx-2 min-w-36 rounded border border-neutral-300 bg-white px-3 py-1.5 text-sm outline-none focus:border-[#2D6A4F]"
                            :value="answers[question.id]"
                            @change="selectQuestion(question.id); setAnswer(question.id, $event.target.value)"
                        >
                            <option value="">Select</option>
                            <template x-for="option in group.options" :key="'summary-select-'+question.id+'-'+option.label">
                                <option :value="option.label" x-text="option.label + '. ' + option.text"></option>
                            </template>
                        </select>
                    </template>
                    <template x-if="!group.options.length">
                        <input
                            type="text"
                            class="mx-2 min-w-40 border-0 border-b-2 border-neutral-300 bg-transparent px-2 py-1 text-sm outline-none focus:border-[#2D6A4F]"
                            :placeholder="'Question ' + question.number"
                            x-model="answers[question.id]"
                            @focus="selectQuestion(question.id)"
                            @input="queueAutosave()"
                        >
                    </template>
                    <button type="button" @click.stop="toggleFlag(question.id)" class="ml-2 text-xs font-semibold" :class="flagged[question.id] ? 'text-amber-700' : 'text-neutral-400'" x-text="flagged[question.id] ? 'Flagged' : 'Flag'"></button>
                </p>
            </template>
        </div>
    </section>
</template>
