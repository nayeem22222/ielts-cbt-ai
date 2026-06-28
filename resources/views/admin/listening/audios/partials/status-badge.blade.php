@props(['status'])

@php
    $tone = match ($status?->value ?? $status) {
        'completed', 'valid' => 'green',
        'processing', 'pending' => 'amber',
        'failed', 'invalid' => 'red',
        default => 'neutral',
    };
    $label = $status instanceof \BackedEnum ? $status->label() : ucfirst((string) $status);
@endphp

<x-ui.badge :tone="$tone">{{ $label }}</x-ui.badge>
