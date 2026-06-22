@props(['items' => [], 'max' => null])

@php
    $values = collect($items)->pluck('value');
    $peak = max($max ?? ($values->max() ?: 1), 1);
@endphp

<div class="space-y-4" data-dashboard-chart>
    @foreach ($items as $item)
        @php
            $width = round(((int) $item['value'] / $peak) * 100);
        @endphp
        <div>
            <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                <span class="font-medium text-neutral-900 dark:text-white">{{ $item['label'] }}</span>
                <span class="aa-muted">{{ number_format((int) $item['value']) }}</span>
            </div>
            <div class="h-3 overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                <div
                    class="h-full rounded-full bg-gradient-to-r from-brand-500 to-sky-400 transition-all duration-500"
                    style="width: {{ max($width, 4) }}%"
                ></div>
            </div>
        </div>
    @endforeach
</div>
