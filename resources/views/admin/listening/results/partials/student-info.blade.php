<x-ui.card title="Student">
    <dl class="grid gap-2 text-sm">
        <div><dt class="aa-muted inline">Name:</dt> <dd class="inline font-medium">{{ $result->user?->name ?? '—' }}</dd></div>
        <div><dt class="aa-muted inline">Email:</dt> <dd class="inline font-medium">{{ $result->user?->email ?? '—' }}</dd></div>
    </dl>
</x-ui.card>
