<x-layouts.reading-exam :title="$test->title" scrollable>
    <div class="reading-test-shell mx-auto max-w-2xl px-4 py-12">
        <a href="{{ route('reading-tests.index') }}" class="text-sm font-medium text-brand-700 hover:underline">← All Reading Tests</a>

        <header class="mt-6 rounded-2xl border border-neutral-200 bg-white p-8 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-600">{{ $test->exam_type_label }}</p>
            <h1 class="mt-2 text-3xl font-bold text-neutral-900">{{ $test->title }}</h1>

            @if ($test->instructions)
                <p class="mt-4 text-sm leading-7 text-neutral-600">{{ $test->instructions }}</p>
            @endif

            <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="font-medium text-neutral-500">Duration</dt>
                    <dd class="mt-1 font-semibold text-neutral-900">{{ $test->duration_minutes }} minutes</dd>
                </div>
                <div>
                    <dt class="font-medium text-neutral-500">Parts</dt>
                    <dd class="mt-1 font-semibold text-neutral-900">{{ $test->passages->count() }}</dd>
                </div>
            </dl>

            <div class="mt-8 flex flex-wrap gap-3">
                @if ($inProgressAttempt)
                    <a
                        href="{{ route('reading-tests.start', $test) }}"
                        class="inline-flex items-center justify-center rounded-xl bg-brand-600 px-6 py-3 text-sm font-semibold text-white hover:bg-brand-700"
                    >
                        Continue Attempt
                    </a>
                @else
                    <a
                        href="{{ route('reading-tests.start', $test) }}"
                        class="inline-flex items-center justify-center rounded-xl bg-brand-600 px-6 py-3 text-sm font-semibold text-white hover:bg-brand-700"
                    >
                        {{ $attempts->isEmpty() ? 'Start Test' : 'Start New Attempt' }}
                    </a>
                @endif

                @if ($latestFinishedAttempt)
                    <a
                        href="{{ route('reading-attempts.result', $latestFinishedAttempt) }}"
                        class="inline-flex items-center justify-center rounded-xl border border-neutral-300 bg-white px-6 py-3 text-sm font-semibold text-neutral-700 hover:bg-neutral-50"
                    >
                        View Latest Result
                    </a>
                @endif
            </div>
        </header>

        @if ($attempts->isNotEmpty())
            <section class="mt-6 rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-neutral-500">Your Attempts</h2>
                <ul class="mt-4 divide-y divide-neutral-100">
                    @foreach ($attempts as $attempt)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                            <div>
                                <p class="text-sm font-medium text-neutral-900">
                                    Attempt #{{ $loop->iteration }}
                                    <span class="font-normal text-neutral-500">
                                        · {{ $attempt->started_at?->format('M j, Y g:i A') ?? '—' }}
                                    </span>
                                </p>
                                <p class="mt-1 text-xs text-neutral-500">
                                    {{ $attempt->status?->label() }}
                                    @if ($attempt->band !== null)
                                        · Band {{ number_format((float) $attempt->band, 1) }}
                                    @endif
                                    @if ($attempt->score !== null)
                                        · Raw {{ number_format((float) $attempt->score, 0) }}
                                    @endif
                                </p>
                            </div>

                            @if ($attempt->status?->value === 'in_progress')
                                <a href="{{ route('reading-tests.start', $test) }}" class="text-sm font-semibold text-brand-700 hover:underline">Continue</a>
                            @elseif (in_array($attempt->status?->value, ['submitted', 'completed'], true))
                                <a href="{{ route('reading-attempts.result', $attempt) }}" class="text-sm font-semibold text-brand-700 hover:underline">View result</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.reading-exam>
