<x-layouts.dashboard :title="$test->title" heading="Test Unavailable">
    <x-ui.card title="This listening test is not available">
        <ul class="list-disc space-y-2 pl-5 text-sm text-red-700">
            @foreach ($reasons as $reason)
                <li>{{ $reason }}</li>
            @endforeach
        </ul>

        @if (! empty($debug))
            <p class="mt-4 font-mono text-xs text-neutral-500">Debug: {{ implode(', ', $debug) }}</p>
        @endif

        <div class="mt-6">
            <x-ui.button href="{{ route('student.listening.tests.index') }}" variant="outline">Back to tests</x-ui.button>
        </div>
    </x-ui.card>
</x-layouts.dashboard>
