<x-layouts.admin :title="$audio->original_name" :heading="$audio->title() ?: $audio->original_name" eyebrow="Listening Audio" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Listening Audio', 'href' => route($routePrefix.'.index')], ['label' => $audio->original_name]]">
    @include('admin.listening.sections.partials.alerts')
    @if (session('status'))
        <x-ui.alert tone="green" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    @if (($pipelineQueue['has_job_for_audio'] ?? false) || (($pipelineQueue['pending_jobs'] ?? 0) > 0 && in_array($audio->processing_status?->value, ['pending', 'processing'], true)))
        <x-ui.alert tone="amber" class="mb-4">
            <p class="font-medium">Queue worker required</p>
            <p class="mt-1 text-sm">
                {{ ($pipelineQueue['pending_jobs'] ?? 0) }} job(s) waiting on the
                <code>{{ config('listening.audio_pipeline.queue', 'listening-audio') }}</code> queue.
                FFmpeg processing will not start until a worker is running.
            </p>
            <p class="mt-2 font-mono text-xs">{{ $pipelineQueue['worker_command'] ?? 'php artisan queue:work database --queue=listening-audio --timeout=900 --tries=3' }}</p>
        </x-ui.alert>
    @endif

    {{-- Action buttons --}}
    <div class="mb-6 flex flex-wrap gap-2">
        @can('update', $audio)
            <x-ui.button href="{{ route($routePrefix.'.edit', $audio) }}" variant="outline">Edit</x-ui.button>
        @endcan
        @can('process', $audio)
            <form method="POST" action="{{ route($routePrefix.'.process', $audio) }}">
                @csrf
                <x-ui.button type="submit">Start Processing</x-ui.button>
            </form>
        @endcan
        @can('retry', $audio)
            <form method="POST" action="{{ route($routePrefix.'.retry', $audio) }}">
                @csrf
                <x-ui.button type="submit" variant="outline">Retry Processing</x-ui.button>
            </form>
            <form method="POST" action="{{ route($routePrefix.'.retry', $audio) }}" onsubmit="return confirm('Force retry will bypass completion check. Continue?')">
                @csrf
                <input type="hidden" name="force" value="1">
                <x-ui.button type="submit" variant="outline">Force Retry</x-ui.button>
            </form>
        @endcan
        @can('generateWaveform', $audio)
            <form method="POST" action="{{ route($routePrefix.'.waveform', $audio) }}">
                @csrf
                <x-ui.button type="submit" variant="outline">Generate Waveform</x-ui.button>
            </form>
        @endcan
        @can('validateAudio', $audio)
            <form method="POST" action="{{ route($routePrefix.'.validate', $audio) }}">
                @csrf
                <x-ui.button type="submit" variant="outline">Validate</x-ui.button>
            </form>
        @endcan
        @can('delete', $audio)
            <form method="POST" action="{{ route($routePrefix.'.destroy', $audio) }}" onsubmit="return confirm('Delete this audio file?')">
                @csrf @method('DELETE')
                <x-ui.button type="submit" variant="danger">Delete</x-ui.button>
            </form>
        @endcan
    </div>

    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Basic Info --}}
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

        {{-- Pipeline Status card (Volume 5A) --}}
        <x-ui.card title="Pipeline Status">
            <dl class="grid gap-3 text-sm">
                @php
                    $currentStage = $audio->pipelineCurrentStage();
                    $playablePath = $audio->playablePath();
                    $meta = is_array($audio->meta) ? $audio->meta : [];
                    $pipelineMeta = is_array($meta['pipeline'] ?? null) ? $meta['pipeline'] : [];
                @endphp
                <div>
                    <dt class="aa-muted">Current Stage</dt>
                    <dd>
                        @if ($currentStage)
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                @if($currentStage === 'completed') bg-green-100 text-green-800
                                @elseif($currentStage === 'failed') bg-red-100 text-red-800
                                @else bg-blue-100 text-blue-800
                                @endif">
                                {{ str_replace('_', ' ', $currentStage) }}
                            </span>
                        @else
                            <span class="aa-muted text-xs">—</span>
                        @endif
                    </dd>
                </div>
                <div><dt class="aa-muted">Pipeline Version</dt><dd>{{ $audio->pipeline_version ?? '—' }}</dd></div>
                <div><dt class="aa-muted">Retry Count</dt><dd>{{ $audio->retry_count ?? 0 }}</dd></div>
                <div>
                    <dt class="aa-muted">Lock Status</dt>
                    <dd>
                        @if ($audio->isLocked())
                            <span class="text-xs font-medium text-amber-700">Locked</span>
                            @if ($audio->processing_locked_at)
                                <span class="aa-muted text-xs"> since {{ $audio->processing_locked_at->diffForHumans() }}</span>
                            @endif
                        @else
                            <span class="text-xs text-gray-500">Unlocked</span>
                        @endif
                    </dd>
                </div>
                <div><dt class="aa-muted">Processing Started</dt><dd>{{ $audio->processing_started_at?->format('Y-m-d H:i:s') ?? '—' }}</dd></div>
                <div><dt class="aa-muted">Processing Finished</dt><dd>{{ $audio->processing_finished_at?->format('Y-m-d H:i:s') ?? '—' }}</dd></div>
                <div><dt class="aa-muted">Last Processed</dt><dd>{{ $audio->last_processed_at?->diffForHumans() ?? '—' }}</dd></div>
                <div>
                    <dt class="aa-muted">Playable Path</dt>
                    <dd class="truncate text-xs font-mono">{{ $playablePath ?? '—' }}</dd>
                </div>
                @if ($audio->processing_error)
                    <div>
                        <dt class="aa-muted text-red-500">Last Error</dt>
                        <dd class="rounded bg-red-50 p-2 text-xs text-red-700">{{ Str::limit($audio->processing_error, 300) }}</dd>
                    </div>
                @endif
            </dl>
            <div class="mt-3 text-xs aa-muted">
                <strong>Health check:</strong> Run <code>php artisan listening:audio:health</code> from server console.
            </div>
        </x-ui.card>

        @include('admin.listening.audios.partials.metadata-card', ['audio' => $audio])

        {{-- Processing Logs table (Volume 5A) --}}
        @php $processingLogs = $audio->processingLogs()->latest()->limit(50)->get(); @endphp
        <x-ui.card title="Processing Logs" class="lg:col-span-2">
            @if ($processingLogs->isEmpty())
                <p class="text-sm aa-muted">No processing logs yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-xs aa-muted">
                                <th class="pb-2 pr-3">Stage</th>
                                <th class="pb-2 pr-3">Status</th>
                                <th class="pb-2 pr-3">Message</th>
                                <th class="pb-2 pr-3">Duration</th>
                                <th class="pb-2 pr-3">Started</th>
                                <th class="pb-2">Finished</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($processingLogs as $log)
                                <tr class="border-b last:border-0 hover:bg-gray-50">
                                    <td class="py-2 pr-3 font-mono text-xs">{{ str_replace('_', ' ', $log->stage) }}</td>
                                    <td class="py-2 pr-3">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                            @if($log->status === 'completed') bg-green-100 text-green-800
                                            @elseif($log->status === 'failed') bg-red-100 text-red-800
                                            @elseif($log->status === 'warning') bg-yellow-100 text-yellow-800
                                            @elseif($log->status === 'skipped') bg-gray-100 text-gray-600
                                            @else bg-blue-100 text-blue-800
                                            @endif">
                                            {{ $log->status }}
                                        </span>
                                    </td>
                                    <td class="py-2 pr-3 text-xs max-w-xs truncate" title="{{ $log->message }}">
                                        {{ $log->message ? Str::limit($log->message, 80) : '—' }}
                                    </td>
                                    <td class="py-2 pr-3 text-xs">{{ $log->durationForHumans() }}</td>
                                    <td class="py-2 pr-3 text-xs">{{ $log->started_at?->format('H:i:s') ?? '—' }}</td>
                                    <td class="py-2 text-xs">{{ $log->finished_at?->format('H:i:s') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>

        @include('admin.listening.audios.partials.section-usage-card', ['usage' => $usage])

        <x-ui.card title="Admin Audio Preview" class="lg:col-span-2">
            @php $audioUrl = app(\App\Services\Listening\Audio\ListeningAudioStorageService::class)->url($audio); @endphp
            @if ($audioUrl)
                <p class="mb-2 text-xs aa-muted">Preview uses the processed/normalized file when available; otherwise it falls back to the original uploaded audio.</p>
                <audio controls class="w-full" src="{{ $audioUrl }}"></audio>
            @else
                <p class="text-sm aa-muted">Audio preview is not available. The original uploaded file may be missing from storage.</p>
            @endif
        </x-ui.card>

        <div class="lg:col-span-2">
            @include('admin.listening.audios.partials.waveform-preview', ['audio' => $audio, 'waveform' => $waveform ?? null])
        </div>

    </div>
</x-layouts.admin>
