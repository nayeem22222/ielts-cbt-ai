@if (!empty($item['audio']['safe_audio_url']))
    <x-ui.card title="Audio Review">
        <audio controls controlsList="nodownload" preload="none" class="w-full"
            data-review-audio
            src="{{ $item['audio']['safe_audio_url'] }}"
            @if (!empty($item['audio']['start_seconds'])) data-start="{{ $item['audio']['start_seconds'] }}" @endif
            @if (!empty($item['audio']['end_seconds'])) data-end="{{ $item['audio']['end_seconds'] }}" @endif
        ></audio>
    </x-ui.card>
@endif
