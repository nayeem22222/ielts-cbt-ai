<x-layouts.exam heading="Reading Tests" title="Reading Tests">
    <div class="mx-auto max-w-3xl space-y-6 py-8">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">IELTS Reading Tests</h1>
            <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-300">Choose a published reading test to begin. Your progress is saved automatically for each test.</p>
        </div>

        <div class="space-y-4">
            @foreach ($tests as $test)
                <article class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <div>
                        <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">{{ $test->title }}</h2>
                        <p class="mt-1 text-sm text-neutral-500">
                            {{ $test->exam_type?->label() }} · {{ $test->total_questions ?? 0 }} questions · {{ gmdate('H:i', $test->duration_seconds ?: 3600) }}
                        </p>
                    </div>
                    <x-ui.button href="{{ route('exam.reading.show', $test) }}">Start Test</x-ui.button>
                </article>
            @endforeach
        </div>
    </div>
</x-layouts.exam>
