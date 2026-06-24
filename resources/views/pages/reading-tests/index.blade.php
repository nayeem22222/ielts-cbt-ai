<x-layouts.reading-exam :title="'Reading Tests'" scrollable>
    <div class="reading-test-shell mx-auto max-w-4xl px-4 py-10">
        <header class="mb-8">
            <h1 class="text-2xl font-bold text-neutral-900">IELTS Reading Tests</h1>
            <p class="mt-2 text-sm text-neutral-600">Select a published reading test to begin.</p>
        </header>

        <div class="space-y-4">
            @foreach ($tests as $test)
                <a
                    href="{{ route('reading-tests.show', $test) }}"
                    class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm transition hover:border-brand-500 hover:shadow-md"
                >
                    <div>
                        <h2 class="text-lg font-semibold text-neutral-900">{{ $test->title }}</h2>
                        <p class="mt-1 text-sm text-neutral-500">
                            {{ $test->exam_type_label }}
                            · {{ $test->duration_minutes }} minutes
                            · {{ $test->passages_count }} {{ Str::plural('passage', $test->passages_count) }}
                        </p>
                    </div>
                    <span class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white">Open</span>
                </a>
            @endforeach
        </div>
    </div>
</x-layouts.reading-exam>
