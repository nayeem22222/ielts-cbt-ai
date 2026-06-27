@php
    $audioMatch = $readiness['transcript_audio_matches'] ?? true;
    $hasTranscript = $section->transcript !== null;
@endphp

<x-ui.card title="Transcript & Passage" id="transcript">
    <p class="mb-4 text-sm aa-muted">Step {{ $hasTranscript ? '2' : '1' }} of 2 — {{ $hasTranscript ? 'Review or change the attached reference' : 'Attach an admin reference transcript for this section' }}.</p>

    @if ($hasTranscript)
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50/60 p-4 dark:border-green-900/40 dark:bg-green-950/20">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-wide text-green-700 dark:text-green-300">Attached transcript</p>
                    <a href="{{ route('admin.listening.transcripts.show', $section->transcript) }}" class="mt-1 block text-lg font-semibold text-green-900 hover:underline dark:text-green-100">
                        {{ $section->transcript->title ?: 'Transcript #'.$section->transcript->id }}
                    </a>
                </div>
                <div class="flex flex-wrap gap-2">
                    @include('admin.listening.transcripts.partials.visibility-badge', ['visibility' => $section->transcript->visibility])
                    @if ($section->transcript->is_official)
                        <x-ui.badge tone="green">Official</x-ui.badge>
                    @endif
                    <x-ui.badge :tone="$audioMatch ? 'green' : 'amber'">{{ $audioMatch ? 'Audio OK' : 'Audio mismatch' }}</x-ui.badge>
                </div>
            </div>

            <p class="mt-3 whitespace-pre-wrap text-sm text-green-900/90 dark:text-green-100/90">{{ \Illuminate\Support\Str::limit(trim((string) $section->transcript->transcript_text), 320) }}</p>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button href="{{ route('admin.listening.transcripts.show', $section->transcript) }}" variant="outline" size="sm">View Full Transcript</x-ui.button>
                @can('update', $section->transcript)
                    <x-ui.button href="{{ route('admin.listening.transcripts.edit', $section->transcript) }}" variant="outline" size="sm">Edit Transcript</x-ui.button>
                @endcan
                @can('update', $section)
                    <form method="POST" action="{{ route($sectionsRoutePrefix.'.transcript.detach', [$listeningTest, $section]) }}" onsubmit="return confirm('Detach transcript from this section?')">
                        @csrf @method('DELETE')
                        <x-ui.button type="submit" variant="danger" size="sm">Detach</x-ui.button>
                    </form>
                @endcan
            </div>
        </div>

        @can('update', $section)
            <details class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-800">
                <summary class="cursor-pointer text-sm font-medium">Change attached transcript</summary>
                <div class="mt-4">
                    @include('admin.listening.transcripts.partials.attach-section-form', [
                        'availableTranscripts' => $availableTranscripts ?? collect(),
                    ])
                </div>
            </details>
        @endcan
    @else
        <div class="mb-4 grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-dashed border-neutral-300 p-3 text-sm dark:border-neutral-700">
                <p class="font-medium">1. Create or pick</p>
                <p class="mt-1 aa-muted">Write transcript text or choose an existing one.</p>
            </div>
            <div class="rounded-xl border border-dashed border-neutral-300 p-3 text-sm dark:border-neutral-700">
                <p class="font-medium">2. Attach here</p>
                <p class="mt-1 aa-muted">Link it to this section for question building.</p>
            </div>
            <div class="rounded-xl border border-dashed border-neutral-300 p-3 text-sm dark:border-neutral-700">
                <p class="font-medium">3. Students stay safe</p>
                <p class="mt-1 aa-muted">Hidden during live test by default.</p>
            </div>
        </div>

        @if (! $section->audio_id)
            <x-ui.alert tone="amber" class="mb-4" title="Tip: attach audio first">
                Linking section audio first helps us recommend matching transcripts automatically.
            </x-ui.alert>
        @endif

        @include('admin.listening.transcripts.partials.attach-section-form', [
            'availableTranscripts' => $availableTranscripts ?? collect(),
        ])
    @endif
</x-ui.card>
