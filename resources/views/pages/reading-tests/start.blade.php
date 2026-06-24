<x-layouts.reading-exam :title="$test->title">
    <div
        x-data="readingTestRenderer(@js($rendererState))"
        x-init="init()"
        class="reading-test-cbt flex h-screen flex-col"
        :class="isLocked ? 'is-submitted' : ''"
    >
        <div
            x-show="saveWarning"
            x-cloak
            class="reading-test-save-warning shrink-0 border-b border-amber-200 bg-amber-50 px-4 py-2 text-center text-sm font-medium text-amber-800"
            x-text="saveWarning"
        ></div>

        <div
            x-show="isLocked"
            x-cloak
            class="reading-test-submitted-banner shrink-0 border-b border-brand-200 bg-brand-50 px-4 py-2 text-center text-sm font-semibold text-brand-800"
        >
            This attempt has been submitted.
            <a :href="endpoints.result" class="underline hover:text-brand-900">View your results</a>
        </div>

        <header class="reading-test-header z-30 grid h-14 shrink-0 grid-cols-3 items-center border-b border-neutral-200 bg-white px-4 shadow-sm">
            <div class="min-w-0">
                <h1 class="truncate text-sm font-semibold text-neutral-800">{{ $test->title }}</h1>
                <p class="truncate text-xs text-neutral-500">{{ $test->exam_type_label }}</p>
            </div>

            <div class="flex justify-center">
                <button
                    type="button"
                    @click="togglePause()"
                    :class="timerClassName"
                    class="flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-semibold"
                    :disabled="isLocked"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span x-text="timerLabel"></span>
                </button>
            </div>

            <div class="flex items-center justify-end gap-2">
                <button type="button" class="reading-test-icon-btn" title="Help" disabled>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </button>
                <button type="button" class="reading-test-icon-btn" title="Notes" @click="openNotesPanel()" :disabled="isLocked">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <button type="button" class="reading-test-toolbar-btn" @click="openReview()" :disabled="isLocked">Review</button>
                <button type="button" class="reading-test-submit-btn" @click="openSubmitModal()" :disabled="isLocked || submitting">Submit</button>
            </div>
        </header>

        <div class="reading-test-mobile-tabs flex gap-2 border-b border-neutral-200 bg-white px-3 py-2 lg:hidden">
            <button type="button" @click="mobilePanel='passage'" class="flex-1 rounded-lg py-2 text-sm font-semibold" :class="mobilePanel==='passage' ? 'bg-brand-600 text-white' : 'bg-neutral-100'">Passage</button>
            <button type="button" @click="mobilePanel='questions'" class="flex-1 rounded-lg py-2 text-sm font-semibold" :class="mobilePanel==='questions' ? 'bg-brand-600 text-white' : 'bg-neutral-100'">Questions</button>
            <button type="button" class="rounded-lg bg-neutral-100 px-3 py-2 text-xs font-semibold" :class="timerClassName" x-text="timerLabel"></button>
        </div>

        <div class="reading-test-panes flex min-h-0 flex-1" x-ref="splitContainer">
            <section
                class="reading-test-passage-pane min-h-0 overflow-y-auto border-r border-neutral-300 bg-white"
                :class="mobilePanel === 'passage' ? 'block' : 'hidden lg:block'"
                :style="isDesktop() ? `width:${passageWidth}%` : ''"
            >
                @foreach ($test->passages as $passage)
                    <div x-show="currentPassageId === {{ $passage->id }}" x-cloak class="p-6 lg:p-8">
                        <x-reading.passage-preview :passage="$passage" />
                    </div>
                @endforeach
            </section>

            <div class="reading-test-divider hidden w-1 shrink-0 cursor-col-resize items-center justify-center bg-neutral-200 hover:bg-neutral-300 lg:flex" @mousedown.prevent="startResize($event)">
                <div class="h-10 w-1 rounded-full bg-neutral-400"></div>
            </div>

            <section class="reading-test-questions-pane relative min-h-0 flex-1 overflow-y-auto bg-[#eef1f4]" :class="mobilePanel === 'questions' ? 'block' : 'hidden lg:block'">
                <div class="space-y-6 p-5 pb-24 lg:p-6">
                    @foreach ($test->passages as $passage)
                        <div x-show="currentPassageId === {{ $passage->id }}" x-cloak>
                            <div class="mb-5 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                                <p class="text-base font-bold text-brand-700">Questions {{ $passage->question_range_label }}</p>
                                @if ($passage->instruction)
                                    <p class="mt-2 text-sm leading-7 text-neutral-600">{{ $passage->instruction }}</p>
                                @endif
                            </div>
                            <div class="space-y-6">
                                @foreach ($passage->groups as $group)
                                    <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                                        <x-reading-test.group :test="$test" :passage="$passage" :group="$group" :renderer="$renderer" />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="reading-test-prev-next pointer-events-none sticky bottom-4 flex justify-end gap-2 px-6">
                    <button type="button" @click="goPrevious()" class="pointer-events-auto reading-test-nav-circle" :disabled="activeQuestionIndex <= 0 || isLocked">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" @click="goNext()" class="pointer-events-auto reading-test-nav-circle" :disabled="activeQuestionIndex >= questions.length - 1 || isLocked">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </section>
        </div>

        <footer class="reading-test-footer z-30 shrink-0 border-t border-neutral-200 bg-white px-3 py-2 shadow-[0_-2px_10px_rgba(0,0,0,0.06)]">
            <div class="reading-test-part-tabs grid gap-2" :style="`grid-template-columns: repeat(${passages.length}, minmax(0, 1fr))`">
                <template x-for="passage in passages" :key="'part-'+passage.id">
                    <div class="rounded-xl border px-2 py-2 transition" :class="expandedPartId === passage.id ? 'border-brand-500/40 bg-emerald-50/50' : 'border-neutral-200 bg-neutral-50'">
                        <button type="button" @click="expandPart(passage.id)" class="mb-1 w-full text-left text-xs font-semibold text-neutral-600">
                            <span x-text="passage.part_label"></span>
                            <span class="block text-[10px] font-normal text-neutral-500" x-text="'Questions ' + passage.question_range"></span>
                            <span class="block text-[10px] font-medium text-brand-700" x-text="partAnsweredLabel(passage)"></span>
                        </button>
                        <div class="flex flex-wrap justify-center gap-1" x-show="expandedPartId === passage.id">
                            <template x-for="question in passage.questions" :key="'nav-'+question.id">
                                <div class="flex flex-col items-center gap-0.5">
                                    <button type="button" @click="selectQuestion(question.number)" class="reading-test-qnav" :class="questionNavClass(question.number)" :data-question-number="question.number" x-text="question.number"></button>
                                    <button type="button" class="reading-test-flag-btn reading-test-flag-btn--nav" title="Flag for review" @click.stop="toggleFlag(question.id, question.number)" :class="isFlagged(question.id) ? 'is-flagged' : ''" :disabled="isLocked">
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor"><path d="M5 2v19.5l2-1.5 2 1.5 2-1.5 2 1.5 2-1.5 2 1.5V2H5z"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </footer>

        @include('pages.reading-tests.partials.review-panel')
        @include('pages.reading-tests.partials.submit-modal')
        @include('pages.reading-tests.partials.pause-modal')
        @include('pages.reading-tests.partials.notes-panel')
        @include('pages.reading-tests.partials.ticket-modal')
    </div>
</x-layouts.reading-exam>
