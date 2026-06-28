@if (! empty($preview['options']))
    <p class="text-sm aa-muted mb-2">{{ $preview['instruction'] ?? '' }}</p>
    <ul class="space-y-1 text-sm">
        @foreach ($preview['options'] as $option)
            <li><span class="font-mono">{{ $option['key'] ?? '' }}</span>. {{ $option['text'] ?? '' }}</li>
        @endforeach
    </ul>
    @if (! empty($preview['questions']))
        <div class="mt-4 border-t pt-3 text-xs aa-muted">
            @foreach ($preview['questions'] as $q)
                @php
                    $ca = $q['correct_answer'] ?? [];
                    $display = is_array($ca) && isset($ca[0]['value']) ? $ca[0]['value'] : json_encode($ca);
                @endphp
                <p>Q{{ $q['number'] }} correct: <strong>{{ $display }}</strong></p>
            @endforeach
        </div>
    @endif
@endif
