<x-layouts.admin title="Listening Results" heading="Listening Results" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Listening'], ['label' => 'Results']]">
    <x-ui.card title="Listening Results">
        @include('admin.listening.results.partials.filters')

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b text-left">
                        <th class="py-2 pr-3">Code</th>
                        <th class="py-2 pr-3">Student</th>
                        <th class="py-2 pr-3">Test</th>
                        <th class="py-2 pr-3">Score</th>
                        <th class="py-2 pr-3">Band</th>
                        <th class="py-2 pr-3">Status</th>
                        <th class="py-2 pr-3">Submitted</th>
                        <th class="py-2 pr-3">Evaluated</th>
                        <th class="py-2 pr-3">Visible</th>
                        <th class="py-2 pr-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($results as $result)
                        <tr class="border-b">
                            <td class="py-2 pr-3">{{ $result->result_code ?? '—' }}</td>
                            <td class="py-2 pr-3">{{ $result->user?->name ?? '—' }}</td>
                            <td class="py-2 pr-3">{{ $result->test?->title ?? '—' }}</td>
                            <td class="py-2 pr-3">{{ number_format((float) $result->raw_score, 0) }}/{{ $result->total_questions }}</td>
                            <td class="py-2 pr-3">{{ $result->band_score !== null ? number_format((float) $result->band_score, 1) : '—' }}</td>
                            <td class="py-2 pr-3">{{ $result->status?->label() ?? '—' }}</td>
                            <td class="py-2 pr-3">{{ $result->submitted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="py-2 pr-3">{{ $result->evaluated_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="py-2 pr-3">{{ $result->is_visible_to_student ? 'Yes' : 'No' }}</td>
                            <td class="py-2 pr-3">
                                <x-ui.button size="sm" href="{{ route('admin.listening.results.show', $result) }}">View</x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="py-4 text-sm aa-muted">No results found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $results->withQueryString()->links() }}</div>
    </x-ui.card>
</x-layouts.admin>
