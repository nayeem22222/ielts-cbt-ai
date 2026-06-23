@props(['cells' => [], 'legend' => []])

<div class="space-y-4">
    @if (! empty($legend))
        <div class="flex flex-wrap gap-3 text-xs aa-muted">
            @foreach ($legend as $tone => $label)
                <span class="inline-flex items-center gap-2">
                    <span @class([
                        'h-3 w-3 rounded',
                        'bg-emerald-200 dark:bg-emerald-500/30' => $tone === 'low',
                        'bg-amber-200 dark:bg-amber-500/30' => $tone === 'medium',
                        'bg-red-200 dark:bg-red-500/30' => $tone === 'high',
                    ])></span>
                    {{ $label }}
                </span>
            @endforeach
        </div>
    @endif

    <div class="grid grid-cols-5 gap-2 sm:grid-cols-8 lg:grid-cols-10">
        @foreach ($cells as $cell)
            @php
                $tone = $cell['tone'] ?? 'medium';
                $classes = match ($tone) {
                    'low' => 'bg-emerald-100 text-emerald-800 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-200',
                    'high' => 'bg-red-100 text-red-800 ring-red-200 dark:bg-red-500/15 dark:text-red-200',
                    default => 'bg-amber-100 text-amber-800 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-200',
                };
            @endphp
            <div class="rounded-2xl p-3 text-center ring-1 {{ $classes }}" title="Q{{ $cell['question_number'] ?? '?' }}">
                <div class="text-sm font-bold">{{ $cell['question_number'] ?? '?' }}</div>
                <div class="mt-1 text-[10px] leading-tight">
                    @if (isset($cell['accuracy_percent']))
                        {{ $cell['accuracy_percent'] }}%
                    @endif
                    @if (isset($cell['time_spent_seconds']))
                        <div>{{ $cell['time_spent_seconds'] }}s</div>
                    @elseif (isset($cell['average_time_seconds']))
                        <div>{{ $cell['average_time_seconds'] }}s avg</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
