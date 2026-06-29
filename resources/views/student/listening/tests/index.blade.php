<x-layouts.dashboard title="Listening Tests" heading="IELTS Listening Tests">
    <div class="mb-6 flex items-center justify-between gap-4">
        <p class="text-sm aa-muted">Choose a published listening test to begin.</p>
        <a href="{{ route('student.dashboard') }}" class="text-sm font-medium text-brand-600 hover:underline">Back to dashboard</a>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($tests as $test)
            @php
                $meta = $testMeta[$test->id] ?? ['warnings' => [], 'startable' => true, 'debug' => []];
            @endphp
            <x-ui.card>
                <h2 class="text-lg font-semibold">{{ $test->title }}</h2>
                <p class="mt-1 text-sm aa-muted">{{ $test->duration_minutes }} min + {{ $test->transfer_time_minutes }} min transfer</p>
                @if (! empty($meta['warnings']))
                    <p class="mt-2 text-xs text-amber-700">Some content is still being prepared. You can still open instructions.</p>
                @endif
                @if (! empty($meta['debug']))
                    <p class="mt-2 font-mono text-xs text-neutral-500">Debug: {{ implode(', ', $meta['debug']) }}</p>
                @endif
                <div class="mt-4">
                    @if ($meta['startable'])
                        <x-ui.button href="{{ route('student.listening.tests.instructions', $test) }}">View Instructions</x-ui.button>
                    @else
                        <x-ui.button href="{{ route('student.listening.tests.instructions', $test) }}" variant="outline">View Details</x-ui.button>
                    @endif
                </div>
            </x-ui.card>
        @empty
            <p class="text-sm aa-muted">No published listening tests are available yet.</p>
        @endforelse
    </div>

    <div class="mt-6">{{ $tests->links() }}</div>
</x-layouts.dashboard>
