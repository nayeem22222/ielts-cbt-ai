<x-layouts.dashboard title="Submitted" heading="Listening Test Submitted">
    <x-ui.card>
        <p class="text-sm">Your listening test attempt has been submitted successfully.</p>
        <p class="mt-2 text-sm aa-muted">{{ $attempt->test?->title }} · submitted {{ $attempt->submitted_at?->format('Y-m-d H:i') }}</p>
        <div class="mt-6 flex gap-3">
            <x-ui.button href="{{ route('student.listening.tests.index') }}">Back to tests</x-ui.button>
        </div>
    </x-ui.card>
</x-layouts.dashboard>
