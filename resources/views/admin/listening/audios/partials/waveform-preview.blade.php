<x-ui.card title="Waveform Preview">
    @if (! empty($waveform['peaks']))
        @php $peaks = array_slice($waveform['peaks'], 0, 200); @endphp
        <svg viewBox="0 0 800 120" class="h-24 w-full rounded-xl border border-neutral-200 bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-900">
            @foreach ($peaks as $index => $peak)
                @php
                    $barWidth = 800 / max(1, count($peaks));
                    $height = max(2, $peak * 110);
                    $x = $index * $barWidth;
                    $y = 120 - $height;
                @endphp
                <rect x="{{ $x }}" y="{{ $y }}" width="{{ max(1, $barWidth - 1) }}" height="{{ $height }}" fill="#2563eb" rx="1" />
            @endforeach
        </svg>
    @elseif ($audio->preview_waveform_path)
        <img src="{{ Storage::disk($audio->disk)->url($audio->preview_waveform_path) }}" alt="Waveform preview" class="w-full rounded-xl border border-neutral-200 dark:border-neutral-700">
    @else
        <x-ui.empty-state title="Waveform not generated yet">Process the audio or queue waveform generation.</x-ui.empty-state>
    @endif
</x-ui.card>
