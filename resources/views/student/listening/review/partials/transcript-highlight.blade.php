@if (!empty($item['highlighted_transcript']))
    <div data-transcript-review @if(!($visibility['allow_student_copy_transcript'] ?? false)) data-disable-copy @endif>
        <x-ui.card title="Transcript Highlight">
            @if (!empty($item['transcript_text_snippet']))
                <p class="mb-3 text-sm font-medium" data-transcript-snippet>{{ $item['transcript_text_snippet'] }}</p>
                <button type="button" class="mb-3 text-sm underline" data-toggle-snippet>Toggle snippet</button>
            @endif
            <div class="space-y-2 text-sm" data-transcript-lines>
                @foreach (($item['highlighted_transcript']['lines'] ?? []) as $line)
                    <p class="rounded px-2 py-1 {{ !empty($line['highlighted']) ? 'bg-yellow-100' : '' }}" data-line="{{ $line['line'] ?? '' }}">
                        @if (!empty($line['speaker']))<span class="font-medium">{{ $line['speaker'] }}:</span> @endif
                        {{ $line['text'] ?? '' }}
                    </p>
                @endforeach
            </div>
        </x-ui.card>
    </div>
@endif
