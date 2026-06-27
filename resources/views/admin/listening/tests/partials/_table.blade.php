@include('admin.listening.tests.partials.filters', ['filters' => $filters])

<x-ui.table>
    <thead>
        <tr class="text-left text-xs uppercase aa-muted">
            <th class="p-4">Title</th>
            <th class="p-4">Code</th>
            <th class="p-4">Type</th>
            <th class="p-4">Difficulty</th>
            <th class="p-4">Status</th>
            <th class="p-4">Active</th>
            <th class="p-4">Featured</th>
            <th class="p-4">Sections</th>
            <th class="p-4">Questions</th>
            <th class="p-4">Published</th>
            <th class="p-4">Created By</th>
            <th class="p-4">Actions</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
        @forelse ($records as $record)
            <tr @class(['opacity-60' => $record->trashed()])>
                <td class="p-4">
                    <div class="font-medium text-neutral-900 dark:text-white">{{ $record->title }}</div>
                    <div class="text-xs aa-muted">{{ $record->slug }}</div>
                </td>
                <td class="p-4 text-sm">{{ $record->test_code }}</td>
                <td class="p-4 text-sm">{{ $record->test_type?->label() ?? '—' }}</td>
                <td class="p-4 text-sm">{{ $record->difficulty_level?->label() ?? '—' }}</td>
                <td class="p-4">@include('admin.listening.tests.partials.status-badge', ['status' => $record->status])</td>
                <td class="p-4 text-sm">{{ $record->is_active ? 'Yes' : 'No' }}</td>
                <td class="p-4 text-sm">{{ $record->is_featured ? 'Yes' : 'No' }}</td>
                <td class="p-4 text-sm">{{ $record->sections_count }}/{{ $totalSections }}</td>
                <td class="p-4 text-sm">{{ $record->questions_count }}/{{ $totalQuestions }}</td>
                <td class="p-4 text-sm aa-muted">{{ $record->published_at?->format('Y-m-d H:i') ?? '—' }}</td>
                <td class="p-4 text-sm">{{ $record->createdBy?->name ?? '—' }}</td>
                <td class="p-4">
                    <div class="flex flex-wrap gap-2">
                        @can('view', $record)
                            <x-ui.button href="{{ route($routePrefix.'.show', $record) }}" size="sm" variant="outline">View</x-ui.button>
                        @endcan
                        @if (! $record->trashed())
                            @can('update', $record)
                                <x-ui.button href="{{ route($routePrefix.'.edit', $record) }}" size="sm" variant="outline">Edit</x-ui.button>
                            @endcan
                            @can('duplicate', $record)
                                <form method="POST" action="{{ route($routePrefix.'.duplicate', $record) }}">@csrf<x-ui.button type="submit" size="sm" variant="outline">Duplicate</x-ui.button></form>
                            @endcan
                            @can('publish', $record)
                                @if ($record->status === \App\Enums\Listening\ListeningTestStatus::Published)
                                    <form method="POST" action="{{ route($routePrefix.'.unpublish', $record) }}">@csrf<x-ui.button type="submit" size="sm" variant="outline">Unpublish</x-ui.button></form>
                                @else
                                    <form method="POST" action="{{ route($routePrefix.'.publish', $record) }}">@csrf<x-ui.button type="submit" size="sm" variant="outline">Publish</x-ui.button></form>
                                @endif
                            @endcan
                            @can('archive', $record)
                                @if ($record->status !== \App\Enums\Listening\ListeningTestStatus::Archived)
                                    <form method="POST" action="{{ route($routePrefix.'.archive', $record) }}">@csrf<x-ui.button type="submit" size="sm" variant="outline">Archive</x-ui.button></form>
                                @endif
                            @endcan
                            @can('delete', $record)
                                <form method="POST" action="{{ route($routePrefix.'.destroy', $record) }}" onsubmit="return confirm('Delete this listening test?')">
                                    @csrf @method('DELETE')
                                    <x-ui.button type="submit" size="sm" variant="danger">Delete</x-ui.button>
                                </form>
                            @endcan
                        @else
                            @can('restore', $record)
                                <form method="POST" action="{{ route($routePrefix.'.restore', $record->id) }}">@csrf<x-ui.button type="submit" size="sm" variant="outline">Restore</x-ui.button></form>
                            @endcan
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="12" class="p-8"><x-ui.empty-state title="No listening tests">Create a listening test to start managing IELTS Listening content.</x-ui.empty-state></td></tr>
        @endforelse
    </tbody>
</x-ui.table>
<div class="mt-4">{{ $records->links() }}</div>
