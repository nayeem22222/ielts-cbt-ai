<x-layouts.dashboard title="Expired" heading="Listening Test Expired">
    <x-ui.card>
        <p class="text-sm">Your listening test time has expired and your attempt was auto-submitted.</p>
        <p class="mt-2 text-sm aa-muted">{{ $attempt->test?->title }}</p>
        <div class="mt-6 flex gap-3">
            <x-ui.button href="{{ route('student.listening.tests.index') }}">Back to tests</x-ui.button>
        </div>
    </x-ui.card>
</x-layouts.dashboard>
