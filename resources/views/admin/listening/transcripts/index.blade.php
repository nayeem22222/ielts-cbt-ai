<x-layouts.admin title="Listening Transcripts" heading="Listening Transcripts" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Listening'], ['label' => 'Transcripts']]">
  @include('admin.listening.sections.partials.alerts')

    <div class="mb-6 flex justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-neutral-900 dark:text-white">Listening Transcripts</h2>
            <p class="text-sm aa-muted">Admin reference transcripts for sections, review, and future audio sync.</p>
        </div>
        @can('create', \App\Models\Listening\ListeningTranscript::class)
            <x-ui.button href="{{ route($routePrefix.'.create') }}">Add Transcript</x-ui.button>
        @endcan
    </div>

    <x-ui.card title="Transcript Directory">
        @include('admin.listening.transcripts.partials.filters')

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-neutral-200 text-left dark:border-neutral-700">
                        <th class="px-3 py-2">Title</th>
                        <th class="px-3 py-2">Audio</th>
                        <th class="px-3 py-2">Language</th>
                        <th class="px-3 py-2">Visibility</th>
                        <th class="px-3 py-2">Official</th>
                        <th class="px-3 py-2">Source</th>
                        <th class="px-3 py-2">Created By</th>
                        <th class="px-3 py-2">Created</th>
                        <th class="px-3 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $transcript)
                        <tr class="border-b border-neutral-100 dark:border-neutral-800">
                            <td class="px-3 py-2 font-medium">{{ $transcript->title ?: 'Transcript #'.$transcript->id }}</td>
                            <td class="px-3 py-2">{{ $transcript->audio?->original_name ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $transcript->language }}</td>
                            <td class="px-3 py-2">@include('admin.listening.transcripts.partials.visibility-badge', ['visibility' => $transcript->visibility])</td>
                            <td class="px-3 py-2">{{ $transcript->is_official ? 'Yes' : 'No' }}</td>
                            <td class="px-3 py-2">{{ $transcript->source_type?->label() ?? 'Manual' }}</td>
                            <td class="px-3 py-2">{{ $transcript->createdBy?->name ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $transcript->created_at?->format('Y-m-d') }}</td>
                            <td class="px-3 py-2">
                                <div class="flex flex-wrap gap-2">
                                    <x-ui.button href="{{ route($routePrefix.'.show', $transcript) }}" variant="outline" size="sm">View</x-ui.button>
                                    @can('update', $transcript)
                                        <x-ui.button href="{{ route($routePrefix.'.edit', $transcript) }}" variant="outline" size="sm">Edit</x-ui.button>
                                    @endcan
                                    @can('delete', $transcript)
                                        <form method="POST" action="{{ route($routePrefix.'.destroy', $transcript) }}" onsubmit="return confirm('Delete this transcript?')">
                                            @csrf @method('DELETE')
                                            <x-ui.button type="submit" variant="danger" size="sm">Delete</x-ui.button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-3 py-6 text-center aa-muted">No transcripts found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $records->links() }}</div>
    </x-ui.card>
</x-layouts.admin>
