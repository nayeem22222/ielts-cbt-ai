<x-layouts.reading-exam :title="$test->title">
    <div x-data="readingPlayer(@js($playerState))" x-init="init()" class="flex h-screen flex-col" :class="isPaused ? 'pointer-events-none' : ''">

        {{-- Top bar --}}
        <header class="z-30 grid h-14 shrink-0 grid-cols-3 items-center border-b border-neutral-200 bg-white px-4 shadow-sm">
            <div class="flex min-w-0 items-center gap-2">
                <svg class="h-5 w-5 shrink-0 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <h1 class="truncate text-sm font-semibold text-neutral-800" x-text="testTitle"></h1>
            </div>

            <button
                type="button"
                @click="togglePause()"
                class="mx-auto hidden items-center gap-2 rounded-full px-4 py-1.5 text-sm font-semibold text-[#2D6A4F] hover:bg-emerald-50 sm:flex"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span x-text="minutesRemainingLabel()"></span>
            </button>

            <div class="flex items-center justify-end gap-2">
                <button type="button" @click="reportModalOpen=true" class="grid h-9 w-9 place-items-center rounded-lg text-red-500 hover:bg-red-50" title="Report a mistake">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </button>
                <button type="button" @click="notesOpen=!notesOpen" class="grid h-9 w-9 place-items-center rounded-lg text-neutral-600 hover:bg-neutral-100" title="Notepad">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <button type="button" @click="reviewOpen=true" class="flex items-center gap-1.5 rounded-lg border border-neutral-200 px-3 py-1.5 text-sm font-semibold text-neutral-700 hover:bg-neutral-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Review
                </button>
                <button
                    type="button"
                    @click="confirmSubmit()"
                    class="flex items-center gap-1.5 rounded-lg bg-[#2D6A4F] px-4 py-2 text-sm font-semibold text-white hover:bg-[#245a42] disabled:opacity-60"
                    :disabled="submitting"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    <span x-text="submitting ? 'Submitting...' : 'Submit'"></span>
                </button>
            </div>
        </header>

        {{-- Mobile passage/questions toggle --}}
        <div class="flex gap-2 border-b border-neutral-200 bg-white px-3 py-2 lg:hidden">
            <button type="button" @click="mobilePanel='passage'" class="flex-1 rounded-lg py-2 text-sm font-semibold" :class="mobilePanel==='passage' ? 'bg-[#2D6A4F] text-white' : 'bg-neutral-100'">Passage</button>
            <button type="button" @click="mobilePanel='questions'" class="flex-1 rounded-lg py-2 text-sm font-semibold" :class="mobilePanel==='questions' ? 'bg-[#2D6A4F] text-white' : 'bg-neutral-100'">Questions</button>
            <button type="button" @click="togglePause()" class="rounded-lg bg-neutral-100 px-3 py-2 text-xs font-semibold text-[#2D6A4F]" x-text="countdownLabel"></button>
        </div>

        {{-- Selection toolbar --}}
        <div
            x-show="selectionToolbar.visible"
            x-cloak
            class="pointer-events-auto fixed z-50 flex gap-1 rounded-xl border border-neutral-200 bg-white p-1 shadow-lg"
            :style="`top:${selectionToolbar.top}px;left:${selectionToolbar.left}px`"
        >
            <button type="button" @click="applyHighlight()" class="rounded-lg px-3 py-1.5 text-xs font-semibold hover:bg-amber-50">Highlight</button>
            <button type="button" @click="notesOpen=true" class="rounded-lg px-3 py-1.5 text-xs font-semibold hover:bg-neutral-100">Note</button>
        </div>

        {{-- Split panes --}}
        <div class="reading-pane flex min-h-0 flex-1" x-ref="splitContainer">
            {{-- Passage --}}
            <section
                class="min-h-0 overflow-y-auto border-r border-neutral-300 bg-white"
                :class="mobilePanel === 'passage' ? 'block' : 'hidden lg:block'"
                :style="window.innerWidth >= 1024 ? `width:${passageWidth}%` : ''"
                @mouseup="handleTextSelection()"
            >
                <div class="p-6 lg:p-8">
                    <template x-if="currentSection">
                        <div>
                            <h2 class="mb-6 text-center text-xl font-bold text-neutral-900" x-text="currentSection.title"></h2>
                            <p class="mb-4 text-sm leading-7 text-neutral-600" x-show="currentSection.instructions" x-text="currentSection.instructions"></p>
                            <article class="font-serif text-[15px] leading-8 text-neutral-900" x-html="renderPassage(currentSection)"></article>
                        </div>
                    </template>
                </div>
            </section>

            <div
                class="hidden w-1 shrink-0 cursor-col-resize items-center justify-center bg-neutral-200 hover:bg-neutral-300 lg:flex"
                @mousedown.prevent="startResize($event)"
            >
                <div class="h-10 w-1 rounded-full bg-neutral-400"></div>
            </div>

            {{-- Questions --}}
            <section
                class="relative min-h-0 flex-1 overflow-y-auto bg-[#eef1f4]"
                :class="mobilePanel === 'questions' ? 'block' : 'hidden lg:block'"
            >
                <div class="space-y-6 p-5 pb-20 lg:p-6">
                    <template x-for="section in sections" :key="'part-'+section.id">
                        <div x-show="currentSectionId === section.id" :id="'part-'+section.id" class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                            <div class="mb-5 border-b border-neutral-100 pb-4">
                                <p class="text-base font-bold text-[#2D6A4F]" x-text="'Questions ' + section.question_from + (section.question_to !== section.question_from ? '–' + section.question_to : '')"></p>
                                <p class="mt-2 text-sm leading-7 text-neutral-600" x-show="section.instructions" x-text="section.instructions"></p>
                            </div>

                            <div class="space-y-6">
                                <template x-for="group in questionGroups(section)" :key="group.id">
                                    <div class="scroll-mt-4" :id="'question-group-'+group.id">
                                        <x-exam.reading-renderers.matching-information />
                                        <x-exam.reading-renderers.matching-headings />
                                        <x-exam.reading-renderers.summary-completion />
                                        <x-exam.reading-renderers.sentence-completion />
                                        <x-exam.reading-renderers.radio-table />
                                        <x-exam.reading-renderers.multiple-choice />
                                        <x-exam.reading-renderers.multiple-answers />
                                        <x-exam.reading-renderers.matching-features />
                                        <x-exam.reading-renderers.matching-sentence-endings />
                                        <x-exam.reading-renderers.short-answer />
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Prev / Next --}}
                <div class="pointer-events-none sticky bottom-4 flex justify-end gap-2 px-6">
                    <button
                        type="button"
                        @click="goPrevious()"
                        class="pointer-events-auto grid h-10 w-10 place-items-center rounded-full border border-neutral-300 bg-white shadow-md hover:bg-neutral-50 disabled:opacity-40"
                        :disabled="activeQuestionIndex <= 0"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button
                        type="button"
                        @click="goNext()"
                        class="pointer-events-auto grid h-10 w-10 place-items-center rounded-full border border-neutral-300 bg-white shadow-md hover:bg-neutral-50 disabled:opacity-40"
                        :disabled="activeQuestionIndex >= questions.length - 1"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </section>
        </div>

        {{-- Bottom navigation --}}
        <footer class="z-30 shrink-0 border-t border-neutral-200 bg-white px-3 py-2 shadow-[0_-2px_10px_rgba(0,0,0,0.06)]">
            <div class="grid grid-cols-3 gap-2">
                <template x-for="section in sections" :key="'footer-'+section.id">
                    <div
                        class="rounded-xl border px-2 py-2 transition"
                        :class="expandedPartId === section.id ? 'border-[#2D6A4F]/40 bg-emerald-50/50' : 'border-neutral-200 bg-neutral-50'"
                    >
                        <button
                            type="button"
                            @click="expandPart(section.id)"
                            class="mb-1 w-full text-left text-xs font-semibold text-neutral-600"
                        >
                            <span x-text="section.part_label + ':'"></span>
                            <span x-text="expandedPartId === section.id ? '' : (' ' + partAnsweredCount(section.id) + ' of ' + section.question_count + ' questions')"></span>
                        </button>
                        <div class="flex flex-wrap justify-center gap-1" x-show="expandedPartId === section.id">
                            <template x-for="question in section.questions" :key="'nav-'+question.id">
                                <button
                                    type="button"
                                    @click="selectQuestion(question.id)"
                                    class="grid h-8 w-8 place-items-center rounded-full text-xs font-bold transition"
                                    :class="questionNavClass(question.id)"
                                    x-text="question.number"
                                ></button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </footer>

        {{-- Notepad drawer --}}
        <div x-show="notesOpen" x-cloak class="pointer-events-auto fixed inset-y-0 right-0 z-40 w-full max-w-sm border-l border-neutral-200 bg-white shadow-2xl">
            <div class="flex h-full flex-col p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold">Notepad</h3>
                    <button type="button" @click="notesOpen=false" class="rounded-lg border px-3 py-1 text-sm">Close</button>
                </div>
                <textarea class="min-h-0 flex-1 rounded-xl border border-neutral-200 bg-neutral-50 p-4 text-sm outline-none focus:border-[#2D6A4F]" x-model="notes[currentSectionId]" placeholder="Notes for this passage..."></textarea>
            </div>
        </div>

        {{-- Pause overlay --}}
        <div x-show="isPaused" x-cloak class="pointer-events-auto fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-8 text-center shadow-2xl">
                <div class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-full bg-emerald-50 text-[#2D6A4F]">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h2 class="text-xl font-bold text-neutral-900">Test Paused</h2>
                <p class="mt-2 text-sm text-neutral-600">The timer is paused. Click resume when you are ready to continue.</p>
                <p class="mt-4 text-2xl font-bold text-[#2D6A4F]" x-text="countdownLabel"></p>
                <button type="button" @click="togglePause()" class="mt-6 w-full rounded-xl bg-[#2D6A4F] px-6 py-3 text-sm font-semibold text-white hover:bg-[#245a42]">Resume Test</button>
            </div>
        </div>

        {{-- Review answers modal --}}
        <div x-show="reviewOpen" x-cloak class="pointer-events-auto fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="reviewOpen=false">
            <div class="flex max-h-[90vh] w-full max-w-2xl flex-col rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b px-6 py-4">
                    <h2 class="text-lg font-bold">Review your answers</h2>
                    <button type="button" @click="reviewOpen=false" class="text-neutral-400 hover:text-neutral-600">&times;</button>
                </div>
                <p class="px-6 pt-3 text-xs text-neutral-500">* This window is to review your answers only. You cannot change answers here.</p>
                <div class="overflow-y-auto px-6 py-4">
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <template x-for="question in questions" :key="'review-'+question.id">
                            <div class="rounded-lg border border-neutral-200 p-2 text-sm">
                                <span class="font-bold" x-text="'Q' + question.number + ':'"></span>
                                <span class="ml-1 text-neutral-700" x-text="reviewAnswerPreview(question.id)"></span>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="border-t px-6 py-4">
                    <button type="button" @click="reviewOpen=false" class="w-full rounded-xl bg-[#2D6A4F] py-3 text-sm font-semibold text-white">Close</button>
                </div>
            </div>
        </div>

        {{-- Submit confirmation modal --}}
        <div x-show="submitModalOpen" x-cloak class="pointer-events-auto fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-8 text-center shadow-2xl">
                <div class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-full bg-emerald-50 text-[#2D6A4F]">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h2 class="text-xl font-bold">Are you sure you want to submit?</h2>
                <p class="mt-3 text-sm text-neutral-600" x-show="unansweredCount > 0">
                    You have <strong x-text="unansweredCount"></strong> unanswered question<span x-show="unansweredCount !== 1">s</span>.
                </p>
                <p class="mt-3 text-sm text-neutral-600" x-show="unansweredCount === 0">All questions have been answered.</p>
                <div class="mt-6 flex gap-3">
                    <button type="button" @click="submitModalOpen=false" class="flex-1 rounded-xl border border-neutral-300 py-3 text-sm font-semibold">Cancel</button>
                    <button type="button" @click="submitTest(true)" class="flex-1 rounded-xl bg-[#2D6A4F] py-3 text-sm font-semibold text-white hover:bg-[#245a42]">Submit and Review Answers</button>
                </div>
            </div>
        </div>

        {{-- Report modal (placeholder) --}}
        <div x-show="reportModalOpen" x-cloak class="pointer-events-auto fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="reportModalOpen=false">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <h2 class="text-lg font-bold">Report a mistake</h2>
                <p class="mt-2 text-sm text-neutral-600">Describe the issue and our team will review it.</p>
                <textarea class="mt-4 w-full rounded-xl border border-neutral-200 p-3 text-sm" rows="4" placeholder="What is wrong?"></textarea>
                <button type="button" @click="reportModalOpen=false" class="mt-4 w-full rounded-xl bg-[#2D6A4F] py-2.5 text-sm font-semibold text-white">Close</button>
            </div>
        </div>
    </div>
</x-layouts.reading-exam>
