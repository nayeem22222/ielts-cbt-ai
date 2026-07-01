<x-layouts.listening-exam :title="$test->title.' — Review'">
    @php
        $reviewState = [
            'sections' => $context_sections,
            'questionMap' => $question_map,
        ];
    @endphp

    @vite(['resources/js/listening-test-result-review.js'])

    <div
        x-data="listeningTestResultReview(@js($reviewState))"
        x-init="init()"
        class="reading-test-result-review flex h-screen flex-col"
    >
        <header class="reading-test-header z-30 flex h-14 shrink-0 items-center justify-between border-b border-neutral-200 bg-white px-4 shadow-sm">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-brand-600">Explanation Review</p>
                <h1 class="truncate text-sm font-semibold text-neutral-900">{{ $test->title }}</h1>
            </div>
            <a href="{{ route('student.listening.attempts.result', $attempt) }}"
               class="rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-semibold text-neutral-700 hover:bg-neutral-50">
                Back to Summary
            </a>
        </header>

        <div class="reading-test-mobile-tabs flex gap-2 border-b border-neutral-200 bg-white px-3 py-2 lg:hidden">
            <button type="button" @click="mobilePanel='context'" class="flex-1 rounded-lg py-2 text-sm font-semibold" :class="mobilePanel==='context' ? 'bg-brand-600 text-white' : 'bg-neutral-100'">Transcript / Audio</button>
            <button type="button" @click="mobilePanel='questions'" class="flex-1 rounded-lg py-2 text-sm font-semibold" :class="mobilePanel==='questions' ? 'bg-brand-600 text-white' : 'bg-neutral-100'">Questions</button>
        </div>

        <div class="flex min-h-0 flex-1">
            <aside class="hidden w-56 shrink-0 overflow-y-auto border-r border-neutral-200 bg-white p-4 lg:block">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-neutral-500">Questions</p>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="item in questionMap" :key="'map-'+item.question_number">
                        <button
                            type="button"
                            class="reading-result-qmap"
                            :class="[mapClass(item), activeQuestionNumber === item.question_number ? 'is-active' : '']"
                            @click="selectQuestion(item.question_number)"
                            x-text="item.question_number"
                        ></button>
                    </template>
                </div>
            </aside>

            <section
                class="reading-test-passage-pane min-h-0 w-full overflow-y-auto border-r border-neutral-200 bg-white lg:w-[42%]"
                :class="mobilePanel === 'context' ? 'block' : 'hidden lg:block'"
            >
                <template x-for="section in sections" :key="'section-'+section.id">
                    <div x-show="activeSectionId === section.id" x-cloak class="p-6 lg:p-8">
                        <h2 class="mb-4 text-center text-xl font-bold text-neutral-900" x-text="'Part ' + section.part_number + (section.title ? ' — ' + section.title : '')"></h2>

                        <template x-if="section.audio_url">
                            <div class="mb-6">
                                <audio class="w-full" controls :src="section.audio_url"></audio>
                            </div>
                        </template>

                        <div class="space-y-2 text-sm leading-7 text-neutral-900" x-show="section.transcript_html" x-html="section.transcript_html"></div>
                        <p class="text-sm text-neutral-500" x-show="!section.transcript_html && !section.audio_url">No transcript or audio review available for this part.</p>
                    </div>
                </template>
            </section>

            <section class="min-h-0 flex-1 overflow-y-auto bg-[#eef1f4]" :class="mobilePanel === 'questions' ? 'block' : 'hidden lg:block'">
                <div class="space-y-6 p-5 lg:p-6">
                    @foreach ($parts as $part)
                        <section class="rounded-2xl border border-neutral-200 bg-white shadow-sm">
                            <div class="border-b border-neutral-200 px-5 py-4">
                                <h2 class="text-lg font-semibold text-neutral-900">
                                    Part {{ $part['part_number'] }} — {{ $part['title'] }}
                                </h2>
                            </div>

                            <div class="divide-y divide-neutral-100">
                                @foreach ($part['questions'] as $item)
                                    @php
                                        $status = $item['status'] ?? 'unanswered';
                                        $statusClasses = match ($status) {
                                            'correct' => 'border-emerald-200 bg-emerald-50',
                                            'incorrect' => 'border-red-200 bg-red-50',
                                            default => 'border-amber-200 bg-amber-50',
                                        };
                                        $badgeClasses = match ($status) {
                                            'correct' => 'bg-emerald-100 text-emerald-800',
                                            'incorrect' => 'bg-red-100 text-red-800',
                                            default => 'bg-amber-100 text-amber-800',
                                        };
                                    @endphp

                                    <article
                                        id="question-{{ $item['question_number'] }}"
                                        class="scroll-mt-4 px-5 py-4"
                                        :class="activeQuestionNumber === {{ $item['question_number'] }} ? 'bg-brand-50/40' : ''"
                                        @click="selectQuestion({{ $item['question_number'] }}, {{ $part['section_id'] ?? 'null' }})"
                                    >
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-neutral-900">
                                                    Question {{ $item['question_number'] }}
                                                    <span class="font-normal text-neutral-500">· {{ $item['question_type_label'] ?? 'Listening' }}</span>
                                                </p>
                                            </div>
                                            <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase {{ $badgeClasses }}">
                                                {{ $status }}
                                            </span>
                                        </div>

                                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                                            <div class="rounded-xl border {{ $statusClasses }} p-4">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Your Answer</p>
                                                <p class="mt-2 text-sm font-medium text-neutral-900">{{ $item['student_answer_display'] ?? '—' }}</p>
                                            </div>
                                            <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Correct Answer</p>
                                                <p class="mt-2 text-sm font-medium text-neutral-900">{{ $item['correct_answer_display'] ?? '—' }}</p>
                                            </div>
                                        </div>

                                        @if (! empty($item['explanation']))
                                            <div class="mt-4 rounded-xl border border-brand-200 bg-brand-50 p-4">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-brand-800">Explanation</p>
                                                <p class="mt-2 text-sm text-brand-900">{{ $item['explanation'] }}</p>
                                            </div>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-layouts.listening-exam>
