@can('update', $section)
    <form method="POST" action="{{ route($sectionsRoutePrefix.'.transcript.attach', [$listeningTest, $section]) }}" class="space-y-4">
        @csrf

        @include('admin.listening.sections.partials.transcript-picker', [
            'transcripts' => $availableTranscripts,
            'section' => $section,
            'listeningTest' => $listeningTest,
            'attachMode' => true,
        ])

        @if ($availableTranscripts->isNotEmpty())
            <div class="flex flex-wrap gap-2 border-t border-neutral-200 pt-4 dark:border-neutral-800">
                <x-ui.button type="submit">{{ $section->transcript_id ? 'Update Attachment' : 'Attach Transcript' }}</x-ui.button>
                @if ($section->transcript_id)
                    <p class="self-center text-xs aa-muted">Choose a different transcript above, then click update.</p>
                @endif
            </div>
        @endif
    </form>
@endcan
