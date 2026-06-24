<x-layouts.admin title="Reading Tickets" heading="Reading Tickets" eyebrow="Test Builder" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Reading Tickets']]">
    <div class="mb-6 flex flex-wrap gap-2">
        <x-ui.button href="{{ route('admin.reading-tickets.index') }}" :variant="empty($status) ? 'primary' : 'outline'">All ({{ array_sum($counts) }})</x-ui.button>
        <x-ui.button href="{{ route('admin.reading-tickets.index', ['status' => 'open']) }}" :variant="$status === 'open' ? 'primary' : 'outline'">Open ({{ $counts['open'] }})</x-ui.button>
        <x-ui.button href="{{ route('admin.reading-tickets.index', ['status' => 'pending']) }}" :variant="$status === 'pending' ? 'primary' : 'outline'">Pending ({{ $counts['pending'] }})</x-ui.button>
        <x-ui.button href="{{ route('admin.reading-tickets.index', ['status' => 'resolved']) }}" :variant="$status === 'resolved' ? 'primary' : 'outline'">Resolved ({{ $counts['resolved'] }})</x-ui.button>
    </div>

    <x-ui.card title="Student Question Tickets">
        <x-ui.table>
            <thead>
                <tr class="text-left text-xs uppercase aa-muted">
                    <th class="p-4">Status</th>
                    <th class="p-4">Student</th>
                    <th class="p-4">Question</th>
                    <th class="p-4">Issue</th>
                    <th class="p-4">Submitted</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                @forelse ($tickets as $ticket)
                    <tr>
                        <td class="p-4"><x-ui.badge>{{ $ticket->status?->label() }}</x-ui.badge></td>
                        <td class="p-4">
                            <p class="font-medium">{{ $ticket->user?->name }}</p>
                            <p class="text-xs aa-muted">{{ $ticket->user?->email }}</p>
                        </td>
                        <td class="p-4">
                            <p class="font-medium">Q{{ $ticket->question_number }} — {{ $ticket->test?->title }}</p>
                            <p class="text-xs aa-muted">{{ Str::limit($ticket->question?->prompt, 60) }}</p>
                        </td>
                        <td class="p-4">{{ $ticket->issue_type?->label() }}</td>
                        <td class="p-4 text-sm">{{ $ticket->created_at?->format('M j, Y g:i A') }}</td>
                        <td class="p-4">
                            <x-ui.button href="{{ route('admin.reading-tickets.show', $ticket) }}" size="sm" variant="outline">View</x-ui.button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8">
                            <x-ui.empty-state title="No tickets">Student question reports will appear here.</x-ui.empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>

        <div class="border-t border-neutral-100 p-4 dark:border-neutral-800">
            {{ $tickets->links() }}
        </div>
    </x-ui.card>
</x-layouts.admin>
