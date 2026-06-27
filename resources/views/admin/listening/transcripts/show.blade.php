<x-layouts.admin :title="$transcript->title ?: 'Transcript'" :heading="$transcript->title ?: 'Listening Transcript'" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Transcripts', 'href' => route($routePrefix.'.index')], ['label' => $transcript->title ?: 'Transcript']]">
    @include('admin.listening.sections.partials.alerts')

    <div class="mb-6 flex flex-wrap justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2">
            @include('admin.listening.transcripts.partials.visibility-badge', ['visibility' => $transcript->visibility])
            @if ($transcript->is_official)
                <x-ui.badge tone="green">Official</x-ui.badge>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            @can('update', $transcript)
                <x-ui.button href="{{ route($routePrefix.'.edit', $transcript) }}">Edit</x-ui.button>
            @endcan
            <x-ui.button href="{{ route($routePrefix.'.index') }}" variant="outline">Back</x-ui.button>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card title="Transcript Details">
            <dl class="space-y-3 text-sm">
                <div><dt class="aa-muted">Linked Audio</dt><dd>{{ $transcript->audio?->original_name ?? '—' }}</dd></div>
                <div><dt class="aa-muted">Language</dt><dd>{{ $transcript->language }}</dd></div>
                <div><dt class="aa-muted">Source</dt><dd>{{ $transcript->source_type?->label() ?? 'Manual' }}</dd></div>
                <div><dt class="aa-muted">Version</dt><dd>{{ $transcript->version ?? 1 }}</dd></div>
                <div><dt class="aa-muted">Created By</dt><dd>{{ $transcript->createdBy?->name ?? '—' }}</dd></div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Readiness">
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div><dt class="aa-muted">Plain Text</dt><dd>{{ $readiness['has_plain_text'] ? 'Yes' : 'No' }}</dd></div>
                <div><dt class="aa-muted">Formatted</dt><dd>{{ $readiness['has_formatted_transcript'] ? 'Yes' : 'No' }}</dd></div>
                <div><dt class="aa-muted">Timestamped</dt><dd>{{ $readiness['has_timestamped_transcript'] ? 'Yes' : 'No' }}</dd></div>
                <div><dt class="aa-muted">Review Ready</dt><dd>{{ $readiness['ready_for_review'] ? 'Yes' : 'No' }}</dd></div>
                <div><dt class="aa-muted">Audio Sync Ready</dt><dd>{{ $readiness['ready_for_audio_sync'] ? 'Yes' : 'No' }}</dd></div>
                <div><dt class="aa-muted">Live Test Visible</dt><dd><x-ui.badge tone="green">Never</x-ui.badge></dd></div>
            </dl>
            @if ($futureReview['may_show_after_submit'])
                <p class="mt-3 text-xs aa-muted">May appear after submission when test settings allow transcript review.</p>
            @else
                <p class="mt-3 text-xs aa-muted">Not configured for post-submit review visibility.</p>
            @endif
        </x-ui.card>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-ui.card title="Plain Transcript">
            <pre class="whitespace-pre-wrap text-sm">{{ $transcript->transcript_text }}</pre>
        </x-ui.card>

        <x-ui.card title="Formatted Transcript Preview">
            @if ($transcript->formatted_transcript)
                <div class="prose prose-sm max-w-none dark:prose-invert">{!! nl2br(e($transcript->formatted_transcript)) !!}</div>
            @else
                <p class="text-sm aa-muted">No formatted transcript.</p>
            @endif
        </x-ui.card>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-ui.card title="Timestamped Transcript">
            @if (! empty($transcript->timestamped_transcript))
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-neutral-200 dark:border-neutral-700">
                                <th class="px-2 py-1 text-left">Line</th>
                                <th class="px-2 py-1 text-left">Speaker</th>
                                <th class="px-2 py-1 text-left">Start</th>
                                <th class="px-2 py-1 text-left">End</th>
                                <th class="px-2 py-1 text-left">Text</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transcript->timestamped_transcript as $line)
                                <tr class="border-b border-neutral-100 dark:border-neutral-800">
                                    <td class="px-2 py-1">{{ $line['line'] ?? '—' }}</td>
                                    <td class="px-2 py-1">{{ $line['speaker'] ?? '—' }}</td>
                                    <td class="px-2 py-1">{{ isset($line['start']) ? number_format((float) $line['start'], 2) : '—' }}</td>
                                    <td class="px-2 py-1">{{ isset($line['end']) ? number_format((float) $line['end'], 2) : '—' }}</td>
                                    <td class="px-2 py-1">{{ $line['text'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm aa-muted">No timestamped transcript.</p>
            @endif

            @can('updateTimestamps', $transcript)
                <form method="POST" action="{{ route($routePrefix.'.timestamps.update', $transcript) }}" class="mt-4 space-y-3 border-t border-neutral-200 pt-4 dark:border-neutral-700">
                    @csrf @method('PUT')
                    @include('admin.listening.transcripts.partials.timestamp-editor')
                    <x-ui.button type="submit" size="sm">Update Timestamps Only</x-ui.button>
                </form>
            @endcan
        </x-ui.card>

        <x-ui.card title="Attached Sections">
            @forelse ($transcript->sections as $attachedSection)
                <div class="mb-3 text-sm">
                    <a href="{{ route('admin.listening.tests.sections.show', [$attachedSection->test, $attachedSection]) }}" class="font-medium text-blue-600 hover:underline dark:text-blue-400">
                        {{ $attachedSection->test?->title }} — Section {{ $attachedSection->section_number }}
                    </a>
                </div>
            @empty
                <p class="text-sm aa-muted">Not attached to any section.</p>
            @endforelse
        </x-ui.card>
    </div>

    <div class="mt-6">
        @include('admin.listening.transcripts.partials.transcript-preview', ['preview' => $passagePreview])
    </div>
</x-layouts.admin>
