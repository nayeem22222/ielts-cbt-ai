<x-layouts.exam :heading="$test->title" :title="$test->title" :time="$timer">
    <div x-data="readingPlayer(@js($playerState))" x-init="init()" class="flex min-h-[calc(100vh-4rem)] flex-col">
        <div class="border-b border-neutral-200 bg-white px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="grid grid-cols-2 gap-2 lg:hidden">
                    <button type="button" @click="mobilePanel='passage'" class="rounded-xl px-3 py-2 text-sm font-semibold" :class="mobilePanel==='passage' ? 'bg-brand-500 text-white' : 'bg-neutral-100 dark:bg-neutral-900'">Passage</button>
                    <button type="button" @click="mobilePanel='questions'" class="rounded-xl px-3 py-2 text-sm font-semibold" :class="mobilePanel==='questions' ? 'bg-brand-500 text-white' : 'bg-neutral-100 dark:bg-neutral-900'">Questions</button>
                </div>
                <span
                    class="rounded-2xl px-3 py-1.5 text-xs font-semibold"
                    :class="{
                        'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300': autosaveStatus === 'saved',
                        'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-200': autosaveStatus === 'pending' || autosaveStatus === 'saving',
                        'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300': autosaveStatus === 'error',
                    }"
                    x-text="autosaveStatus === 'saved' ? 'Autosaved' : (autosaveStatus === 'error' ? 'Save failed' : 'Saving...')"
                ></span>
            </div>
        </div>

        <div class="grid flex-1 lg:grid-cols-[1.05fr_.95fr]">
            <section class="border-r border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-950" :class="mobilePanel === 'passage' ? 'block' : 'hidden lg:block'">
                <div class="sticky top-16 z-20 border-b border-neutral-200 bg-white/95 p-4 backdrop-blur dark:border-neutral-800 dark:bg-neutral-950/95">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap gap-2">
                            <template x-for="section in sections" :key="section.id">
                                <button type="button" @click="selectSection(section.id)" class="rounded-xl px-3 py-1.5 text-xs font-semibold" :class="currentSectionId === section.id ? 'bg-brand-500 text-white' : 'bg-neutral-100 text-neutral-700 dark:bg-neutral-900 dark:text-neutral-300'" x-text="'Passage ' + section.sort_order"></button>
                            </template>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="toggleHighlightMode()" class="rounded-xl border px-3 py-2 text-sm font-semibold dark:border-neutral-800" :class="highlightMode ? 'border-amber-400 bg-amber-50 text-amber-800 dark:bg-amber-500/10 dark:text-amber-200' : ''">Highlight</button>
                            <button type="button" @click="notesOpen=!notesOpen" class="rounded-xl border px-3 py-2 text-sm font-semibold dark:border-neutral-800">Notes</button>
                        </div>
                    </div>
                </div>

                <div class="p-5">
                    <template x-if="currentSection">
                        <div>
                            <div class="mb-4 flex items-center gap-2">
                                <span class="inline-flex rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-200" x-text="currentSection.title"></span>
                                <span class="text-xs aa-muted" x-show="highlightMode">Select text to highlight</span>
                            </div>
                            <p class="mb-4 rounded-2xl bg-neutral-50 p-4 text-sm aa-muted dark:bg-neutral-900" x-show="currentSection.instructions" x-text="currentSection.instructions"></p>
                            <article class="prose prose-neutral max-w-none leading-7 dark:prose-invert" @mouseup="applyHighlight()" x-html="renderPassage(currentSection)"></article>
                        </div>
                    </template>
                </div>

                <div x-show="notesOpen" x-cloak class="border-t border-neutral-200 bg-neutral-50 p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <label class="mb-2 block text-sm font-semibold text-neutral-900 dark:text-white">Passage Notes</label>
                    <textarea class="min-h-28 w-full rounded-2xl border border-neutral-200 bg-white p-4 text-sm outline-none dark:border-neutral-800 dark:bg-neutral-950" x-model="notes[currentSectionId]" placeholder="Write notes for this passage..."></textarea>
                </div>
            </section>

            <section class="bg-neutral-50 dark:bg-neutral-950" :class="mobilePanel === 'questions' ? 'block' : 'hidden lg:block'">
                <div class="sticky top-16 z-20 border-b border-neutral-200 bg-neutral-50/95 p-4 backdrop-blur dark:border-neutral-800 dark:bg-neutral-950/95">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Questions</h2>
                        <span class="rounded-2xl bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-200" x-text="countdownLabel"></span>
                    </div>
                </div>

                <div class="space-y-4 p-5">
                    <template x-for="question in questions" :key="question.id">
                        <div class="rounded-3xl border bg-white p-4 dark:bg-neutral-900" :class="activeQuestionId === question.id ? 'border-brand-400 ring-2 ring-brand-200 dark:ring-brand-500/30' : 'border-neutral-200 dark:border-neutral-800'" :id="'question-' + question.id">
                            <div class="mb-3 flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-brand-600" x-text="'Question ' + question.number"></p>
                                    <p class="mt-1 text-xs aa-muted" x-text="question.type_label"></p>
                                </div>
                                <button type="button" @click="toggleFlag(question.id)" class="rounded-xl border px-3 py-1.5 text-xs font-semibold dark:border-neutral-700" :class="flagged[question.id] ? 'border-amber-400 bg-amber-50 text-amber-800 dark:bg-amber-500/10 dark:text-amber-200' : ''" x-text="flagged[question.id] ? 'Flagged' : 'Flag'"></button>
                            </div>
                            <div class="mb-4 text-sm leading-7 text-neutral-800 dark:text-neutral-100" x-html="(question.prompt || '').replace(/\n/g, '<br>')"></div>

                            <template x-if="question.options.length">
                                <div class="space-y-2">
                                    <template x-for="option in question.options" :key="option.label">
                                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-neutral-200 p-3 text-sm dark:border-neutral-800">
                                            <input type="radio" :name="'question-' + question.id" :value="option.text" x-model="answers[question.id]" class="mt-1">
                                            <span><strong x-text="option.label + '.'"></strong> <span x-text="option.text"></span></span>
                                        </label>
                                    </template>
                                </div>
                            </template>
                            <template x-if="!question.options.length">
                                <input type="text" class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm outline-none dark:border-neutral-800 dark:bg-neutral-950" x-model="answers[question.id]" placeholder="Type your answer">
                            </template>
                        </div>
                    </template>
                </div>

                <div class="border-t border-neutral-200 p-5 dark:border-neutral-800">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide aa-muted">Question Navigator</h3>
                    <div class="grid grid-cols-5 gap-2 sm:grid-cols-8 lg:grid-cols-5 xl:grid-cols-8">
                        <template x-for="item in navigatorQuestions" :key="item.id">
                            <button type="button" @click="selectQuestion(item.id)" class="h-9 rounded-xl text-sm font-semibold transition" :class="item.active ? 'bg-brand-500 text-white ring-2 ring-brand-300' : (item.flagged ? 'bg-amber-100 text-amber-800 ring-1 ring-amber-300 dark:bg-amber-500/20 dark:text-amber-200' : (item.answered ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300'))" x-text="item.number"></button>
                        </template>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-3 text-xs aa-muted">
                        <span><span class="mr-1 inline-block h-3 w-3 rounded bg-brand-500"></span> Active</span>
                        <span><span class="mr-1 inline-block h-3 w-3 rounded bg-emerald-200"></span> Answered</span>
                        <span><span class="mr-1 inline-block h-3 w-3 rounded bg-amber-200"></span> Flagged</span>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-layouts.exam>
