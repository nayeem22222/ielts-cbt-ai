@php
    $readyAudioCount = 0;
@endphp

<div class="flex items-end justify-between gap-3">
    <div class="flex-1">
        <x-ui.select name="audio_id" label="Audio">
            <option value="">No audio selected</option>
            @foreach ($audios as $audio)
                @php
                    $playablePath = $audio->playablePath();
                    $playableExists = $playablePath !== null
                        && \Illuminate\Support\Facades\Storage::disk($audio->disk ?: (string) config('listening.audio.disk', 'public'))->exists($playablePath);
                    $isReady = $audio->processing_status === \App\Enums\Listening\ListeningAudioProcessingStatus::Completed
                        && $audio->validation_status === \App\Enums\Listening\ListeningAudioValidationStatus::Valid
                        && $audio->duration_seconds !== null
                        && $playableExists;
                    $readyAudioCount += $isReady ? 1 : 0;
                @endphp
                <option
                    value="{{ $audio->id }}"
                    @selected((string) old('audio_id', $section->audio_id ?? '') === (string) $audio->id)
                    @disabled(! $isReady)
                >
                    {{ $audio->original_name }}
                    @if ($audio->duration_seconds)
                        · {{ $audio->duration_seconds }}s
                    @endif
                    @if ($isReady)
                        · Ready
                    @else
                        · {{ $audio->processing_status?->label() ?? $audio->processing_status }}
                        · {{ $audio->validation_status?->label() ?? $audio->validation_status }}
                        · Not ready
                    @endif
                </option>
            @endforeach
        </x-ui.select>
    </div>

    <a href="{{ route('admin.listening.audios.index') }}" class="mb-1 whitespace-nowrap text-sm font-medium text-brand-600 hover:text-brand-700 hover:underline">
        Manage Listening Audio
    </a>
</div>

@if ($readyAudioCount === 0)
    <p class="mt-1 text-xs text-amber-700">No ready audio found. Upload and process audio first.</p>
@else
    <p class="mt-1 text-xs aa-muted">Ready audio files are selectable. Failed, invalid, or incomplete audio remains visible but disabled.</p>
@endif
