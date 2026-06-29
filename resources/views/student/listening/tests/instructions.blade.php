<x-layouts.dashboard :title="$test->title" :heading="$test->title">
    @if (session('error'))
        <x-ui.alert tone="red" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    <x-ui.card title="Instructions">
        <div class="prose max-w-none text-sm dark:prose-invert">
            {!! nl2br(e($test->instructions ?: 'Follow the on-screen instructions during the listening test.')) !!}
        </div>
        <dl class="mt-6 grid gap-3 text-sm md:grid-cols-2">
            <div><dt class="aa-muted">Duration</dt><dd>{{ $test->duration_minutes }} minutes</dd></div>
            <div><dt class="aa-muted">Transfer time</dt><dd>{{ $test->transfer_time_minutes }} minutes</dd></div>
            <div><dt class="aa-muted">Sections</dt><dd>{{ $test->active_sections_count ?? 0 }}</dd></div>
            <div><dt class="aa-muted">Questions</dt><dd>{{ $test->active_questions_count ?? 0 }}</dd></div>
        </dl>

        @if (! empty($warnings))
            <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <p class="font-medium">Before you start</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($warnings as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
                <p class="mt-3 text-xs">If audio is not ready, you will see a message in the player. Please contact your admin.</p>
            </div>
        @endif

        @if (! empty($debug))
            <p class="mt-4 font-mono text-xs text-neutral-500">Debug: {{ implode(', ', $debug) }}</p>
        @endif

        <div class="mt-6 flex flex-wrap gap-3">
            <form method="POST" action="{{ route('student.listening.tests.start', $test) }}">
                @csrf
                <x-ui.button type="submit">Start Listening Test</x-ui.button>
            </form>
            <x-ui.button href="{{ route('student.listening.tests.index') }}" variant="outline">Back</x-ui.button>
        </div>
    </x-ui.card>
</x-layouts.dashboard>
