<x-layouts.dashboard title="Result Unavailable" heading="Listening Result">
    <x-ui.card>
        <p class="text-sm">Your result could not be prepared right now. Please contact support.</p>
        <p class="mt-2 text-sm aa-muted">{{ $attempt?->test?->title }}</p>
        <div class="mt-6">
            <x-ui.button href="{{ route('student.listening.tests.index') }}">Back to tests</x-ui.button>
        </div>
    </x-ui.card>
</x-layouts.dashboard>
