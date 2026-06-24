<x-layouts.reading-exam :title="$test->title.' — Results'" scrollable>

    @php

        $minutes = intdiv((int) ($summary['time_spent_seconds'] ?? 0), 60);

        $seconds = ((int) ($summary['time_spent_seconds'] ?? 0)) % 60;

        $timeSpentLabel = sprintf('%02d:%02d', $minutes, $seconds);

    @endphp



    <div class="reading-test-shell mx-auto max-w-6xl px-4 py-10 pb-16">

        <div class="mb-8 text-center">

            <p class="text-xs font-semibold uppercase tracking-wide text-brand-600">Reading Test Results</p>

            <h1 class="mt-2 text-3xl font-bold text-neutral-900">{{ $test->title }}</h1>

            <p class="mt-2 text-sm text-neutral-600">

                {{ $summary['exam_type'] ?? 'Academic' }}

                · Submitted {{ $attempt->submitted_at?->format('M j, Y g:i A') ?? '—' }}

            </p>

        </div>



        <div class="grid gap-6 lg:grid-cols-[220px_1fr]">

            <aside class="reading-result-sidebar order-2 lg:order-1">

                <div class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">

                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-neutral-500">Question Map</p>

                    <div class="flex flex-wrap gap-1.5">

                        @foreach ($question_map as $item)

                            <a

                                href="{{ route('reading-attempts.result.review', $attempt) }}#question-{{ $item['question_number'] }}"

                                class="reading-result-qmap is-{{ $item['status'] }}"

                                title="Question {{ $item['question_number'] }}"

                            >{{ $item['question_number'] }}</a>

                        @endforeach

                    </div>

                    <div class="mt-4 space-y-1 text-xs text-neutral-600">

                        <p><span class="reading-result-legend is-correct"></span> Correct</p>

                        <p><span class="reading-result-legend is-incorrect"></span> Incorrect</p>

                        <p><span class="reading-result-legend is-unanswered"></span> Unanswered</p>

                        <p><span class="reading-result-legend is-flagged"></span> Flagged</p>

                    </div>

                </div>

            </aside>



            <div class="order-1 space-y-8 lg:order-2">

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                    <div class="rounded-2xl border border-neutral-200 bg-white p-5 text-center shadow-sm">

                        <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Band Score</p>

                        <p class="mt-2 text-4xl font-bold text-brand-600">{{ number_format((float) ($summary['band'] ?? 0), 1) }}</p>

                    </div>

                    <div class="rounded-2xl border border-neutral-200 bg-white p-5 text-center shadow-sm">

                        <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Raw Score</p>

                        <p class="mt-2 text-4xl font-bold text-neutral-900">

                            {{ number_format((float) ($summary['raw_score'] ?? 0), 0) }}

                            <span class="text-lg font-medium text-neutral-500">/ {{ (int) ($summary['total_questions'] ?? 0) }}</span>

                        </p>

                    </div>

                    <div class="rounded-2xl border border-neutral-200 bg-white p-5 text-center shadow-sm">

                        <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Time Spent</p>

                        <p class="mt-2 text-3xl font-bold text-neutral-900">{{ $timeSpentLabel }}</p>

                        <p class="mt-1 text-xs text-neutral-500">{{ (int) ($summary['duration_minutes'] ?? 0) }} min test</p>

                    </div>

                    <div class="rounded-2xl border border-neutral-200 bg-white p-5 text-center shadow-sm">

                        <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Attempted</p>

                        <p class="mt-2 text-3xl font-bold text-neutral-900">

                            {{ (int) ($summary['attempted'] ?? 0) }}

                            <span class="text-lg font-medium text-neutral-500">/ {{ (int) ($summary['total_questions'] ?? 0) }}</span>

                        </p>

                    </div>

                </div>



                <div class="grid gap-4 md:grid-cols-3">

                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">

                        <p class="text-sm font-semibold text-emerald-800">Correct</p>

                        <p class="mt-1 text-3xl font-bold text-emerald-700">{{ (int) ($summary['correct'] ?? 0) }}</p>

                    </div>

                    <div class="rounded-2xl border border-red-200 bg-red-50 p-5">

                        <p class="text-sm font-semibold text-red-800">Incorrect</p>

                        <p class="mt-1 text-3xl font-bold text-red-700">{{ (int) ($summary['incorrect'] ?? 0) }}</p>

                    </div>

                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">

                        <p class="text-sm font-semibold text-amber-800">Unanswered</p>

                        <p class="mt-1 text-3xl font-bold text-amber-700">{{ (int) ($summary['unanswered'] ?? 0) }}</p>

                    </div>

                </div>



                @if (! empty($part_analytics))

                    <section class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">

                        <h2 class="text-lg font-semibold text-neutral-900">Review Analytics by Part</h2>

                        <div class="mt-4 grid gap-4 md:grid-cols-3">

                            @foreach ($part_analytics as $part)

                                <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">

                                    <p class="text-sm font-semibold text-neutral-800">Part {{ $part['part_number'] }}</p>

                                    <p class="mt-1 text-xs text-neutral-500">{{ $part['title'] }}</p>

                                    <dl class="mt-3 space-y-1 text-sm">

                                        <div class="flex justify-between"><dt class="text-emerald-700">Correct</dt><dd class="font-semibold">{{ $part['correct'] }}</dd></div>

                                        <div class="flex justify-between"><dt class="text-red-700">Incorrect</dt><dd class="font-semibold">{{ $part['incorrect'] }}</dd></div>

                                        <div class="flex justify-between"><dt class="text-amber-700">Unanswered</dt><dd class="font-semibold">{{ $part['unanswered'] }}</dd></div>

                                        <div class="flex justify-between border-t border-neutral-200 pt-2"><dt>Accuracy</dt><dd class="font-bold text-brand-700">{{ $part['accuracy_percent'] }}%</dd></div>

                                    </dl>

                                </div>

                            @endforeach

                        </div>

                    </section>

                @endif



                @if (! empty($insights))

                    <section class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">

                        <h2 class="text-lg font-semibold text-neutral-900">Reading Insights — Weak Areas</h2>

                        <div class="mt-4 space-y-3">

                            @foreach ($insights as $insight)

                                <div class="rounded-xl border border-neutral-200 px-4 py-3">

                                    <div class="flex items-center justify-between gap-3">

                                        <p class="text-sm font-semibold text-neutral-900">{{ $insight['label'] }}</p>

                                        <p class="text-sm font-bold {{ $insight['accuracy_percent'] < 60 ? 'text-red-600' : 'text-neutral-700' }}">

                                            {{ $insight['correct'] }}/{{ $insight['total'] }} correct

                                        </p>

                                    </div>

                                    <div class="mt-2 h-2 rounded-full bg-neutral-100">

                                        <div class="h-2 rounded-full {{ $insight['accuracy_percent'] < 60 ? 'bg-red-500' : 'bg-brand-500' }}" style="width: {{ $insight['accuracy_percent'] }}%"></div>

                                    </div>

                                </div>

                            @endforeach

                        </div>

                    </section>

                @endif



                <div class="flex flex-wrap justify-center gap-3">

                    <a href="{{ route('reading-attempts.result.review', $attempt) }}"

                       class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">

                        Question Review

                    </a>

                    <a href="{{ route('reading-tests.index') }}"

                       class="rounded-lg border border-neutral-300 bg-white px-5 py-2.5 text-sm font-semibold text-neutral-700 hover:bg-neutral-50">

                        Back to Reading Tests

                    </a>

                </div>

            </div>

        </div>

    </div>

</x-layouts.reading-exam>

