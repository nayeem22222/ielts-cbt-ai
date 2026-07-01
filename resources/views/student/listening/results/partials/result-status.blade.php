@php
    $status = $result->status?->value ?? 'pending';
    $label = $result->status?->label() ?? ucfirst($status);
@endphp
<span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
    @if ($status === 'ready') bg-green-100 text-green-800
    @elseif ($status === 'pending') bg-yellow-100 text-yellow-800
    @elseif ($status === 'failed') bg-red-100 text-red-800
    @else bg-neutral-100 text-neutral-700
    @endif">{{ $label }}</span>
