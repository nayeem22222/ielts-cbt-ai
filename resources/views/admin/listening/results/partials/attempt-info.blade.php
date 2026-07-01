<x-ui.card title="Attempt">
    <dl class="grid gap-2 text-sm">
        <div><dt class="aa-muted inline">Attempt ID:</dt> <dd class="inline font-medium">{{ $result->attempt?->id ?? '—' }}</dd></div>
        <div><dt class="aa-muted inline">Status:</dt> <dd class="inline font-medium">{{ $result->attempt?->status?->label() ?? '—' }}</dd></div>
        <div><dt class="aa-muted inline">Submitted:</dt> <dd class="inline font-medium">{{ $result->submitted_at?->format('Y-m-d H:i') ?? '—' }}</dd></div>
        <div><dt class="aa-muted inline">Duration:</dt> <dd class="inline font-medium">{{ $result->listening_duration_seconds ? $result->listening_duration_seconds.'s' : '—' }}</dd></div>
    </dl>
</x-ui.card>
