<x-layouts.exam :heading="$test->title" :title="$test->title" :time="$timer">
    <div x-data="readingPlayer(@js($playerState))" x-init="init()" class="flex min-h-[calc(100vh-4rem)] flex-col pb-44 lg:pb-36">
        {{-- Toolbar --}}
        <div class="border-b border-neutral-200 bg-white px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="grid grid-cols-2 gap-2 lg:hidden">
                        <button type="button" @click="mobilePanel='passage'" class="rounded-xl px-3 py-2 text-sm font-semibold" :class="mobilePanel==='passage' ? 'bg-brand-500 text-white' : 'bg-neutral-100 dark:bg-neutral-900'">Passage</button>
                        <button type="button" @click="mobilePanel='questions'" class="rounded-xl px-3 py-2 text-sm font-semibold" :class="mobilePanel==='questions' ? 'bg-brand-500 text-white' : 'bg-neutral-100 dark:bg-neutral-900'">Questions</button>
                    </div>
                    <button type="button" @click="toggleHighlightMode()" class="rounded-xl border px-3 py-2 text-sm font-semibold dark:border-neutral-800" :class="highlightMode ? 'border-amber-400 bg-amber-50 text-amber-800 dark:bg-amber-500/10 dark:text-amber-200' : ''">Highlight</button>
                    <button type="button" @click="notesOpen=!notesOpen" class="rounded-xl border px-3 py-2 text-sm font-semibold dark:border-neutral-800">Notepad</button>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="rounded-2xl px-3 py-1.5 text-xs font-semibold"
                        :class="{
                            'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300': autosaveStatus === 'saved',
                            'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-200': autosaveStatus === 'pending' || autosaveStatus === 'saving',
                            'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300': autosaveStatus === 'error',
                        }"
                        x-text="autosaveStatus === 'saved' ? 'Autosaved' : (autosaveStatus === 'error' ? 'Save failed' : 'Saving...')"
                    ></span>
                    <button
                        type="button"
                        @click="confirmSubmit()"
                        class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="submitting"
                        x-text="submitting ? 'Submitting...' : 'Submit Test'"
                    ></button>
                </div>
            </div>
        </div>

        {{-- Selection toolbar (ProQyz-style) --}}
        <div
            x-show="selectionToolbar.visible"
            x-cloak
            class="fixed z-50 flex gap-1 rounded-xl border border-neutral-200 bg-white p-1 shadow-lg dark:border-neutral-700 dark:bg-neutral-900"
            :style="`top:${selectionToolbar.top}px;left:${selectionToolbar.left}px`"
        >
            <button type="button" @click="applyHighlight()" class="rounded-lg px-3 py-1.5 text-xs font-semibold hover:bg-amber-50 dark:hover:bg-amber-500/10">Highlight</button>
            <button type="button" @click="notesOpen=true" class="rounded-lg px-3 py-1.5 text-xs font-semibold hover:bg-neutral-100 dark:hover:bg-neutral-800">Note</button>
        </div>

        <div class="grid flex-1 lg:grid-cols-2">
            {{-- Passage panel --}}
            <section class="border-r border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-950" :class="mobilePanel === 'passage' ? 'block' : 'hidden lg:block'">
                <div class="sticky top-16 z-20 border-b border-neutral-200 bg-white/95 p-4 backdrop-blur dark:border-neutral-800 dark:bg-neutral-950/95">
                    <div class="flex flex-wrap gap-2">
                        <template x-for="section in sections" :key="section.id">
                            <button
                                type="button"
                                @click="selectSection(section.id)"
                                class="rounded-xl px-3 py-1.5 text-xs font-semibold"
                                :class="currentSectionId === section.id ? 'bg-brand-500 text-white' : 'bg-neutral-100 text-neutral-700 dark:bg-neutral-900 dark:text-neutral-300'"
                                x-text="section.part_label"
                            ></button>
                        </template>
                    </div>
                </div>

                <div class="p-5" @mouseup="handleTextSelection()">
                    <template x-if="currentSection">
                        <div>
                            <h2 class="mb-4 text-xl font-bold text-neutral-900 dark:text-white" x-text="currentSection.title"></h2>
                            <p class="mb-4 rounded-2xl bg-neutral-50 p-4 text-sm aa-muted dark:bg-neutral-900" x-show="currentSection.instructions" x-text="currentSection.instructions"></p>
                            <article class="prose prose-neutral max-w-none leading-8 dark:prose-invert" x-html="renderPassage(currentSection)"></article>
                        </div>
                    </template>
                </div>
            </section>

            {{-- Questions panel (grouped by Part) --}}
            <section class="bg-neutral-50 dark:bg-neutral-950" :class="mobilePanel === 'questions' ? 'block' : 'hidden lg:block'">
                <div class="sticky top-16 z-20 border-b border-neutral-200 bg-neutral-50/95 p-4 backdrop-blur dark:border-neutral-800 dark:bg-neutral-950/95">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Questions</h2>
                        <span class="rounded-2xl bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-200" x-text="countdownLabel"></span>
                    </div>
                </div>

                <div class="space-y-8 p-5">
                    <template x-for="section in sections" :key="'part-'+section.id">
                        <div class="rounded-3xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900" :id="'part-'+section.id">
                            <div class="mb-5 border-b border-neutral-100 pb-4 dark:border-neutral-800">
                                <p class="text-xs font-semibold uppercase tracking-wide text-brand-600" x-text="section.part_label"></p>
                                <h3 class="mt-1 text-lg font-bold text-neutral-900 dark:text-white" x-text="section.title"></h3>
                                <p class="mt-2 text-sm font-semibold aa-muted" x-show="section.question_from" x-text="'Questions ' + section.question_from + (section.question_to !== section.question_from ? '–' + section.question_to : '')"></p>
                                <p class="mt-3 text-sm leading-7 aa-muted" x-show="section.instructions" x-text="section.instructions"></p>
                            </div>

                            <div class="space-y-6">
                                <template x-for="question in section.questions" :key="question.id">
                                    <div
                                        class="scroll-mt-28 rounded-2xl border p-4 transition"
                                        :class="activeQuestionId === question.id ? 'border-brand-400 bg-brand-50/40 ring-2 ring-brand-200 dark:bg-brand-500/5 dark:ring-brand-500/30' : 'border-neutral-100 dark:border-neutral-800'"
                                        :id="'question-'+question.id"
                                        @click="selectQuestion(question.id)"
                                    >
                                        <div class="mb-2 flex items-start justify-between gap-3">
                                            <p class="text-sm leading-7 text-neutral-800 dark:text-neutral-100">
                                                <span class="mr-2 inline-flex h-7 min-w-7 items-center justify-center rounded-lg bg-neutral-100 px-1 text-xs font-bold dark:bg-neutral-800" x-text="question.number"></span>
                                                <span x-html="formatPrompt(question.prompt)"></span>
                                            </p>
                                            <button
                                                type="button"
                                                @click.stop="toggleFlag(question.id)"
                                                class="shrink-0 rounded-lg border px-2 py-1 text-xs font-semibold dark:border-neutral-700"
                                                :class="flagged[question.id] ? 'border-amber-400 bg-amber-50 text-amber-800 dark:bg-amber-500/10 dark:text-amber-200' : ''"
                                                x-text="flagged[question.id] ? 'Flagged' : 'Flag'"
                                            ></button>
                                        </div>
                                        <p class="mb-1 text-xs aa-muted" x-text="question.type_label"></p>
                                        <x-exam.reading-question-input />
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </section>
        </div>

        {{-- Notepad drawer --}}
        <div x-show="notesOpen" x-cloak class="fixed inset-y-0 right-0 z-40 w-full max-w-md border-l border-neutral-200 bg-white p-5 shadow-2xl dark:border-neutral-800 dark:bg-neutral-950">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-bold text-neutral-900 dark:text-white">Notepad</h3>
                <button type="button" @click="notesOpen=false" class="rounded-lg border px-3 py-1 text-sm dark:border-neutral-700">Close</button>
            </div>
            <textarea class="min-h-[70vh] w-full rounded-2xl border border-neutral-200 bg-neutral-50 p-4 text-sm outline-none dark:border-neutral-800 dark:bg-neutral-900" x-model="notes[currentSectionId]" placeholder="Write notes for this passage..."></textarea>
        </div>

        {{-- ProQyz-style bottom part navigator --}}
        <footer class="fixed bottom-0 left-0 right-0 z-30 border-t border-neutral-200 bg-white/95 backdrop-blur dark:border-neutral-800 dark:bg-neutral-950/95">
            <div class="mx-auto max-w-7xl space-y-3 px-4 py-3">
                <template x-for="section in sections" :key="'nav-'+section.id">
                    <div>
                        <p class="mb-2 text-xs font-semibold aa-muted">
                            <span x-text="section.part_label + ':'"></span>
                            <span x-text="partAnsweredCount(section.id) + ' of ' + section.question_count + ' questions'"></span>
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="question in section.questions" :key="'nav-q-'+question.id">
                                <button
                                    type="button"
                                    @click="selectQuestion(question.id)"
                                    class="h-8 min-w-8 rounded-lg px-2 text-xs font-bold transition"
                                    :class="questionNavClass(question.id)"
                                    x-text="question.number"
                                ></button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </footer>
    </div>
</x-layouts.exam>
