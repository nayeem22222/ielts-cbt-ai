@php
    $status = $infrastructure[$activeTab->value] ?? [];
@endphp

<x-ui.card title="Infrastructure Status" subtitle="Read-only runtime status from application configuration" class="mb-6">
    <dl class="grid gap-4 md:grid-cols-2">
        @foreach ($status as $key => $value)
            @if (is_array($value))
                @continue
            @endif
            <div class="rounded-2xl border border-neutral-200 p-4 dark:border-neutral-800">
                <dt class="text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ str_replace('_', ' ', $key) }}</dt>
                <dd class="mt-1 text-sm font-medium text-neutral-900 dark:text-white">{{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}</dd>
            </div>
        @endforeach
    </dl>

    @if (isset($status['status']))
        <div class="mt-4">
            <x-ui.badge :tone="$status['status'] === 'healthy' ? 'green' : ($status['status'] === 'degraded' ? 'amber' : 'red')">
                {{ ucfirst($status['status']) }} — {{ $status['detail'] ?? '' }}
            </x-ui.badge>
        </div>
    @endif
</x-ui.card>
