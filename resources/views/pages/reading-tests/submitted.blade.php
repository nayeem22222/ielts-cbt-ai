<x-layouts.reading-exam :title="'Test Submitted'" scrollable>
    <div class="reading-test-shell mx-auto max-w-lg px-4 py-20 text-center">
        <div class="rounded-2xl border border-neutral-200 bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-bold text-neutral-900">Reading Test Submitted</h1>
            <p class="mt-3 text-sm text-neutral-600">
                Your answers for <strong>{{ $test->title }}</strong> have been submitted successfully.
            </p>
            <p class="mt-2 text-xs text-neutral-500">
                Submitted at {{ $attempt->submitted_at?->format('M j, Y g:i A') ?? '—' }}
            </p>
            <p class="mt-4 text-sm text-neutral-500">
                Results and band scoring will be available in a future update.
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('reading-tests.index') }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    Back to Reading Tests
                </a>
            </div>
        </div>
    </div>
</x-layouts.reading-exam>
