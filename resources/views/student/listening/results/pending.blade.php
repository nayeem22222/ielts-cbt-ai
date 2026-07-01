<x-layouts.dashboard title="Result Pending" heading="Listening Result">
    <x-ui.card>
        <p class="text-sm">Your Listening result is being prepared.</p>
        <p class="mt-2 text-sm aa-muted">{{ $attempt?->test?->title }}</p>
        <div class="mt-6 flex gap-3">
            <x-ui.button href="{{ url()->current() }}">Refresh</x-ui.button>
            <x-ui.button variant="secondary" href="{{ route('student.listening.tests.index') }}">Back to tests</x-ui.button>
        </div>
    </x-ui.card>
</x-layouts.dashboard>
