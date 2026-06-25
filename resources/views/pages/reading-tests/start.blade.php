<x-layouts.reading-exam :title="$test->title">

    <div

        x-data="readingTestRenderer(@js($rendererState))"

        x-init="init()"

        class="reading-test-cbt ielts-reading-cbt flex h-screen flex-col"

        :class="[isLocked ? 'is-submitted' : '', (saveWarning || isLocked) ? 'has-top-banner' : '']"

        role="application"

        aria-label="IELTS Reading Computer-Based Test"

    >

        <div

            x-show="saveWarning"

            x-cloak

            class="reading-test-save-warning shrink-0 flex items-center justify-center gap-3 border-b border-amber-200 bg-amber-50 px-4 py-2 text-center text-sm font-medium text-amber-800"

            role="alert"

        >

            <span x-text="saveWarning"></span>

            <button type="button" class="rounded px-2 py-0.5 text-xs font-semibold text-amber-900 hover:bg-amber-100" @click="saveWarning = null">Dismiss</button>

        </div>



        <div

            x-show="isLocked"

            x-cloak

            class="reading-test-submitted-banner shrink-0 border-b border-brand-200 bg-brand-50 px-4 py-2 text-center text-sm font-semibold text-brand-800"

            role="status"

        >

            This attempt has been submitted.

            <a :href="endpoints.result" class="underline hover:text-brand-900">View your results</a>

        </div>



        <header class="reading-test-header grid grid-cols-[1fr_auto_1fr] items-center gap-3">

            <div class="reading-test-header__brand min-w-0">

                <div class="reading-test-header__icon" aria-hidden="true">

                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>

                    </svg>

                </div>

                <div class="min-w-0">

                    <h1 class="reading-test-header__title">{{ $test->title }}</h1>

                    <p class="reading-test-header__meta">{{ $test->exam_type_label }} · Reading</p>

                </div>

            </div>



            <div class="flex justify-center">

                <button

                    type="button"

                    @click="togglePause()"

                    :class="timerClassName"

                    class="reading-test-timer flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-semibold"

                    :disabled="isLocked"

                    aria-live="polite"

                    :aria-label="'Time remaining: ' + timerLabel"

                >

                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>

                    <span x-text="timerLabel"></span>

                </button>

            </div>



            <div class="flex items-center justify-end gap-1.5 sm:gap-2">

                <button type="button" class="reading-test-icon-btn" title="Help" aria-label="Help (coming soon)" disabled>

                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>

                </button>

                <button type="button" class="reading-test-icon-btn" title="Notes" aria-label="Open notes panel" @click="openNotesPanel()" :disabled="isLocked">

                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>

                </button>

                <button type="button" class="reading-test-toolbar-btn hidden sm:inline-flex" @click="openReview()" :disabled="isLocked" aria-label="Open review panel (Alt+R)">Review</button>

                <button type="button" class="reading-test-submit-btn" @click="openSubmitModal()" :disabled="isLocked || submitting" aria-label="Submit test">

                    <span>Submit</span>

                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>

                </button>

            </div>

        </header>



        <div class="reading-test-mobile-tabs flex gap-2 lg:hidden" role="tablist" aria-label="Passage and questions">

            <button type="button" role="tab" :aria-selected="mobilePanel==='passage'" @click="mobilePanel='passage'" class="flex-1 rounded-lg py-2 text-sm font-semibold" :class="mobilePanel==='passage' ? 'bg-brand-600 text-white' : 'bg-neutral-100'">Passage</button>

            <button type="button" role="tab" :aria-selected="mobilePanel==='questions'" @click="mobilePanel='questions'" class="flex-1 rounded-lg py-2 text-sm font-semibold" :class="mobilePanel==='questions' ? 'bg-brand-600 text-white' : 'bg-neutral-100'">Questions</button>

            <button type="button" class="rounded-lg bg-neutral-100 px-3 py-2 text-xs font-semibold" :class="timerClassName" x-text="timerLabel" aria-label="Timer"></button>

        </div>



        <div class="reading-test-panes flex min-h-0 flex-1" x-ref="splitContainer">

            <section

                class="reading-test-passage-pane min-h-0 overflow-y-auto"

                :class="mobilePanel === 'passage' ? 'block' : 'hidden lg:block'"

                :style="isDesktop() ? `width:${passageWidth}%` : ''"

                aria-label="Reading passage"

            >

                @foreach ($test->passages as $passage)

                    <div x-show="currentPassageId === {{ $passage->id }}" x-cloak class="reading-test-passage-pane__content">

                        <x-reading.passage-preview :passage="$passage" />

                    </div>

                @endforeach

            </section>



            <div

                class="reading-test-divider hidden shrink-0 cursor-col-resize items-center justify-center lg:flex"

                role="separator"

                aria-orientation="vertical"

                aria-label="Resize passage and question panels"

                tabindex="0"

                @mousedown.prevent="startResize($event)"

            >

                <div></div>

            </div>



            <section

                class="reading-test-questions-pane relative min-h-0 flex-1 overflow-y-auto"

                :class="mobilePanel === 'questions' ? 'block' : 'hidden lg:block'"

                aria-label="Questions"

            >

                <div class="space-y-5 p-4 pb-24 lg:p-6">

                    @foreach ($test->passages as $passage)

                        <div x-show="currentPassageId === {{ $passage->id }}" x-cloak>

                            <div class="reading-test-part-intro mb-5">

                                @php

                                    $passageQuestions = $renderer->questionsForPassage($passage);

                                    $displayRange = $renderer->questionRangeLabel($passageQuestions, $passage);

                                @endphp

                                <p class="reading-test-part-intro__label">Questions {{ $displayRange }}</p>

                                @if ($passage->instruction)

                                    <p class="mt-2 text-sm leading-relaxed text-neutral-600">{{ $passage->instruction }}</p>

                                @endif

                            </div>

                            <div class="space-y-5">

                                @foreach ($passage->groups as $group)

                                    <div class="reading-test-group-shell">

                                        <div class="reading-test-group-shell__inner">

                                            <x-reading-test.group :test="$test" :passage="$passage" :group="$group" :renderer="$renderer" />

                                        </div>

                                    </div>

                                @endforeach

                            </div>

                        </div>

                    @endforeach

                </div>



                <div class="reading-test-prev-next pointer-events-none sticky bottom-4 flex justify-end gap-2 px-4 lg:px-6">

                    <button type="button" @click="goPrevious()" class="pointer-events-auto reading-test-nav-circle" :disabled="activeQuestionIndex <= 0 || isLocked" aria-label="Previous question (Alt+P)">

                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>

                    </button>

                    <button type="button" @click="goNext()" class="pointer-events-auto reading-test-nav-circle" :disabled="activeQuestionIndex >= questions.length - 1 || isLocked" aria-label="Next question (Alt+N)">

                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>

                    </button>

                </div>

            </section>

        </div>



        <footer class="reading-test-footer" role="navigation" aria-label="Question navigator">

            <div class="reading-test-part-tabs grid gap-2" :style="`grid-template-columns: repeat(${passages.length}, minmax(0, 1fr))`">

                <template x-for="passage in passages" :key="'part-'+passage.id">

                    <div
                        class="reading-test-part-tab transition"
                        :class="expandedPartId === passage.id ? 'is-expanded' : ''"
                        role="button"
                        tabindex="0"
                        :aria-expanded="expandedPartId === passage.id ? 'true' : 'false'"
                        :aria-label="passage.part_label + ', Questions ' + passage.question_range"
                        @click="expandPart(passage.id)"
                        @keydown.enter.prevent="expandPart(passage.id)"
                        @keydown.space.prevent="expandPart(passage.id)"
                    >

                        <div class="reading-test-part-tab-head">

                            <span class="reading-test-part-tab-head__row">

                                <span class="reading-test-part-tab-head__part" x-text="passage.part_label"></span>

                                <span class="reading-test-part-tab-head__range" x-text="'Questions ' + passage.question_range"></span>

                            </span>

                            <span class="reading-test-part-tab-head__status" x-text="partAnsweredLabel(passage)"></span>

                        </div>

                        <div class="reading-test-part-tab__questions flex flex-wrap justify-center gap-1" x-show="expandedPartId === passage.id" x-cloak @click.stop>

                            <template x-for="question in passage.questions" :key="'nav-'+question.id">

                                <button

                                    type="button"

                                    @click="selectQuestion(question.number)"

                                    class="reading-test-qnav"

                                    :class="questionNavClass(question.number)"

                                    :data-question-number="question.number"

                                    :aria-label="'Question ' + question.number"

                                    :aria-current="question.number === currentQuestionNumber ? 'true' : 'false'"

                                    x-text="question.number"

                                ></button>

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


