<x-layouts.admin title="Listening Audio Library" heading="Listening Audio Library" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Listening'], ['label' => 'Audio Library']]">
    @include('admin.listening.sections.partials.alerts')
    @if (session('status'))
        <x-ui.alert tone="green" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="mb-6 flex justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-neutral-900 dark:text-white">Listening Audio Library</h2>
            <p class="text-sm aa-muted">Upload, process, validate, and manage listening test audio files.</p>
        </div>
        @can('create', \App\Models\Listening\ListeningAudio::class)
            <x-ui.button href="{{ route($routePrefix.'.create') }}">Upload Audio</x-ui.button>
        @endcan
    </div>

    <x-ui.card title="Audio Files">
        @include('admin.listening.audios.partials.filters')

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-neutral-200 text-left dark:border-neutral-700">
                        <th class="px-3 py-2">Original Name</th>
                        <th class="px-3 py-2">Format</th>
                        <th class="px-3 py-2">Duration</th>
                        <th class="px-3 py-2">File Size</th>
                        <th class="px-3 py-2">Processing</th>
                        <th class="px-3 py-2">Validation</th>
                        <th class="px-3 py-2">Uploaded By</th>
                        <th class="px-3 py-2">Created</th>
                        <th class="px-3 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $audio)
                        <tr class="border-b border-neutral-100 dark:border-neutral-800">
                            <td class="px-3 py-2 font-medium">{{ $audio->title() ?: $audio->original_name }}</td>
                            <td class="px-3 py-2">{{ strtoupper((string) ($audio->format ?: $audio->extension)) }}</td>
                            <td class="px-3 py-2">{{ $audio->duration_seconds ? $audio->duration_seconds.'s' : '—' }}</td>
                            <td class="px-3 py-2">{{ number_format(($audio->file_size ?? 0) / 1024 / 1024, 2) }} MB</td>
                            <td class="px-3 py-2">@include('admin.listening.audios.partials.status-badge', ['status' => $audio->processing_status])</td>
                            <td class="px-3 py-2">@include('admin.listening.audios.partials.validation-badge', ['status' => $audio->validation_status])</td>
                            <td class="px-3 py-2">{{ $audio->uploadedBy?->name ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $audio->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-3 py-2">
                                <div class="flex flex-wrap gap-2">
                                    <x-ui.button href="{{ route($routePrefix.'.show', $audio) }}" variant="outline" size="sm">View</x-ui.button>
                                    @can('update', $audio)
                                        <x-ui.button href="{{ route($routePrefix.'.edit', $audio) }}" variant="outline" size="sm">Edit</x-ui.button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-3 py-6 text-center aa-muted">No audio files found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $records->links() }}</div>
    </x-ui.card>
</x-layouts.admin>
