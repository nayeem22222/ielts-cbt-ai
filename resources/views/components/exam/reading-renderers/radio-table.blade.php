<template x-if="group.renderer === 'radio-table'">
    <section class="rounded-lg border border-neutral-300 bg-white">
        <div class="border-b border-neutral-200 px-4 py-3">
            <h3 class="text-base font-bold text-[#2D6A4F]" x-text="group.title"></h3>
            <p class="mt-1 text-sm leading-6 text-neutral-600" x-text="group.instruction"></p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[32rem] border-collapse text-sm">
                <thead>
                    <tr class="bg-neutral-50">
                        <th class="w-10 border-b border-r border-neutral-200 px-2 py-2"></th>
                        <th class="border-b border-r border-neutral-200 px-3 py-2 text-left font-semibold text-neutral-600">Statement</th>
                        <template x-for="option in group.options" :key="'radio-head-'+option.text">
                            <th class="border-b border-neutral-200 px-3 py-2 text-center font-semibold text-neutral-700" x-text="option.text"></th>
                        </template>
                        <th class="w-16 border-b border-l border-neutral-200 px-2 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="question in group.questions" :key="'radio-row-'+question.id">
                        <tr :id="'question-'+question.id" :class="activeQuestionId === question.id ? 'bg-emerald-50/70' : 'bg-white'">
                            <td class="border-r border-t border-neutral-200 px-2 py-3 align-top font-bold" x-text="question.number"></td>
                            <td class="border-r border-t border-neutral-200 px-3 py-3 align-top leading-6 text-neutral-800" x-html="formatPrompt(question.prompt)"></td>
                            <template x-for="option in groupOptionsForQuestion(group, question)" :key="'radio-cell-'+question.id+'-'+option.text">
                                <td class="border-t border-neutral-200 px-3 py-3 text-center">
                                    <button
                                        type="button"
                                        @click.stop="selectQuestion(question.id); setAnswer(question.id, option.text)"
                                        class="mx-auto grid h-5 w-5 place-items-center rounded-full border-2"
                                        :class="answers[question.id] === option.text ? 'border-[#2D6A4F] bg-[#2D6A4F]' : 'border-neutral-300 bg-white'"
                                    >
                                        <span class="h-2 w-2 rounded-full bg-white" x-show="answers[question.id] === option.text"></span>
                                    </button>
                                </td>
                            </template>
                            <td class="border-l border-t border-neutral-200 px-2 py-3 text-center">
                                <button type="button" @click.stop="toggleFlag(question.id)" class="text-xs font-semibold" :class="flagged[question.id] ? 'text-amber-700' : 'text-neutral-400'" x-text="flagged[question.id] ? 'Flagged' : 'Flag'"></button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </section>
</template>
