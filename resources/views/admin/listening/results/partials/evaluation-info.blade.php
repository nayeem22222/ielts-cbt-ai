<x-ui.card title="Evaluation">
    <dl class="grid gap-2 text-sm">
        <div><dt class="aa-muted inline">Evaluation ID:</dt> <dd class="inline font-medium">{{ $result->evaluation?->id ?? '—' }}</dd></div>
        <div><dt class="aa-muted inline">Version:</dt> <dd class="inline font-medium">{{ $result->evaluation?->evaluation_version ?? '—' }}</dd></div>
        <div><dt class="aa-muted inline">Status:</dt> <dd class="inline font-medium">{{ $result->evaluation?->status?->label() ?? '—' }}</dd></div>
        <div><dt class="aa-muted inline">Evaluated:</dt> <dd class="inline font-medium">{{ $result->evaluated_at?->format('Y-m-d H:i') ?? '—' }}</dd></div>
        @if ($failureReason)
            <div><dt class="aa-muted inline">Failure:</dt> <dd class="inline font-medium text-red-700">{{ $failureReason }}</dd></div>
        @endif
    </dl>
</x-ui.card>
