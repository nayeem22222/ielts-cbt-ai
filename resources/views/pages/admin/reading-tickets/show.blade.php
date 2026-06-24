<x-layouts.admin title="Ticket #{{ $ticket->id }}" heading="Reading Ticket #{{ $ticket->id }}" eyebrow="Reading Tickets" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Reading Tickets', 'href' => route('admin.reading-tickets.index')], ['label' => 'Ticket #'.$ticket->id]]">
    @if (session('status'))
        <x-ui.alert class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card title="Ticket Details">
            <dl class="space-y-3 text-sm">
                <div><dt class="font-semibold">Status</dt><dd><x-ui.badge>{{ $ticket->status?->label() }}</x-ui.badge></dd></div>
                <div><dt class="font-semibold">Student</dt><dd>{{ $ticket->user?->name }} ({{ $ticket->user?->email }})</dd></div>
                <div><dt class="font-semibold">Test</dt><dd>{{ $ticket->test?->title }}</dd></div>
                <div><dt class="font-semibold">Question</dt><dd>Q{{ $ticket->question_number }} — {{ $ticket->question?->prompt }}</dd></div>
                <div><dt class="font-semibold">Passage</dt><dd>Part {{ $ticket->question?->group?->passage?->part_number }} — {{ $ticket->question?->group?->passage?->title }}</dd></div>
                <div><dt class="font-semibold">Issue Type</dt><dd>{{ $ticket->issue_type?->label() }}</dd></div>
                <div><dt class="font-semibold">Message</dt><dd class="whitespace-pre-wrap">{{ $ticket->message }}</dd></div>
                <div><dt class="font-semibold">Submitted</dt><dd>{{ $ticket->created_at?->format('M j, Y g:i A') }}</dd></div>
            </dl>
        </x-ui.card>

        <div class="space-y-6">
            <x-ui.card title="Admin Reply">
                @if ($ticket->admin_reply)
                    <p class="mb-4 whitespace-pre-wrap text-sm">{{ $ticket->admin_reply }}</p>
                @endif

                <form method="POST" action="{{ route('admin.reading-tickets.reply', $ticket) }}" class="space-y-3">
                    @csrf
                    <x-ui.textarea name="admin_reply" label="Reply" rows="5" required>{{ old('admin_reply', $ticket->admin_reply) }}</x-ui.textarea>
                    <x-ui.button type="submit">Send Reply</x-ui.button>
                </form>
            </x-ui.card>

            @if ($ticket->status?->value !== 'resolved')
                <form method="POST" action="{{ route('admin.reading-tickets.resolve', $ticket) }}" onsubmit="return confirm('Mark this ticket as resolved?')">
                    @csrf
                    <x-ui.button type="submit" variant="outline">Resolve Ticket</x-ui.button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.admin>
