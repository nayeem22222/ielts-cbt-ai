@php
    $tone = match ($status) {
        \App\Enums\Listening\ListeningTestStatus::Published => 'green',
        \App\Enums\Listening\ListeningTestStatus::Archived => 'neutral',
        default => 'amber',
    };
@endphp
<x-ui.badge :tone="$tone">{{ $status?->label() ?? 'Draft' }}</x-ui.badge>
