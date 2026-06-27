@php
    $tone = match ($visibility?->value ?? $visibility) {
        'review_visible' => 'blue',
        'admin_only' => 'amber',
        default => 'neutral',
    };
    $label = $visibility instanceof \App\Enums\Listening\ListeningTranscriptVisibility
        ? $visibility->label()
        : (\App\Enums\Listening\ListeningTranscriptVisibility::tryFrom((string) $visibility)?->label() ?? 'Hidden');
@endphp
<x-ui.badge :tone="$tone">{{ $label }}</x-ui.badge>
