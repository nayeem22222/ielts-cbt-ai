<x-layouts.admin :title="$audio->original_name" :heading="$audio->title() ?: $audio->original_name" eyebrow="Listening Audio" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Listening Audio', 'href' => route($routePrefix.'.index')], ['label' => $audio->original_name]]">
    @include('admin.listening.sections.partials.alerts')
    @if (session('status'))
        <x-ui.alert tone="green" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="mb-6 flex flex-wrap gap-2">
        @can('update', $audio)
            <x-ui.button href="{{ route($routePrefix.'.edit', $audio) }}" variant="outline">Edit</x-ui.button>
        @endcan
        @can('process', $audio)
            <form method="POST" action="{{ route($routePrefix.'.process', $audio) }}">@csrf<x-ui.button type="submit">Process</x-ui.button></form>
        @endcan
        @can('retry', $audio)
            <form method="POST" action="{{ route($routePrefix.'.retry', $audio) }}">@csrf<x-ui.button type="submit" variant="outline">Retry</x-ui.button></form>
        @endcan
        @can('generateWaveform', $audio)
            <form method="POST" action="{{ route($routePrefix.'.waveform', $audio) }}">@csrf<x-ui.button type="submit" variant="outline">Generate Waveform</x-ui.button></form>
        @endcan
        @can('validateAudio', $audio)
            <form method="POST" action="{{ route($routePrefix.'.validate', $audio) }}">@csrf<x-ui.button type="submit" variant="outline">Validate</x-ui.button></form>
        @endcan
        @can('delete', $audio)
            <form method="POST" action="{{ route($routePrefix.'.destroy', $audio) }}" onsubmit="return confirm('Delete this audio file?')">
                @csrf @method('DELETE')
                <x-ui.button type="submit" variant="danger">Delete</x-ui.button>
            </form>
        @endcan
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card title="Basic Info">
            <dl class="grid gap-3 text-sm">
                <div><dt class="aa-muted">Original Name</dt><dd>{{ $audio->original_name }}</dd></div>
                <div><dt class="aa-muted">Stored Name</dt><dd>{{ $audio->stored_name }}</dd></div>
                <div><dt class="aa-muted">Processing</dt><dd>@include('admin.listening.audios.partials.status-badge', ['status' => $audio->processing_status])</dd></div>
                <div><dt class="aa-muted">Validation</dt><dd>@include('admin.listening.audios.partials.validation-badge', ['status' => $audio->validation_status])</dd></div>
                <div><dt class="aa-muted">Uploaded By</dt><dd>{{ $audio->uploadedBy?->name ?? '—' }}</dd></div>
                <div><dt class="aa-muted">Ready for Sections</dt><dd>{{ ($readiness['is_ready'] ?? false) ? 'Yes' : 'No' }}</dd></div>
            </dl>
        </x-ui.card>

        @include('admin.listening.audios.partials.metadata-card', ['audio' => $audio])
        @include('admin.listening.audios.partials.processing-log', ['audio' => $audio])
        @include('admin.listening.audios.partials.section-usage-card', ['usage' => $usage])

        <x-ui.card title="Admin Audio Preview" class="lg:col-span-2">
            @php $audioUrl = app(\App\Services\Listening\Audio\ListeningAudioStorageService::class)->url($audio); @endphp
            @if ($audioUrl)
                <audio controls class="w-full" src="{{ $audioUrl }}"></audio>
            @else
                <p class="text-sm aa-muted">Processed audio is not available yet.</p>
            @endif
        </x-ui.card>

        <div class="lg:col-span-2">
            @include('admin.listening.audios.partials.waveform-preview', ['audio' => $audio, 'waveform' => $waveform ?? null])
        </div>
    </div>
</x-layouts.admin>
