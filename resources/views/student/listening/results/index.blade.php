<x-layouts.dashboard title="Listening Results" heading="Listening Results">
    <x-ui.card title="Your Listening Results">
        @if ($results->isEmpty())
            <p class="text-sm aa-muted">No listening results yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="py-2 pr-4">Test</th>
                            <th class="py-2 pr-4">Code</th>
                            <th class="py-2 pr-4">Submitted</th>
                            <th class="py-2 pr-4">Score</th>
                            <th class="py-2 pr-4">Band</th>
                            <th class="py-2 pr-4">Status</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($results as $result)
                            <tr class="border-b">
                                <td class="py-2 pr-4">{{ $result->test?->title ?? '—' }}</td>
                                <td class="py-2 pr-4">{{ $result->result_code ?? '—' }}</td>
                                <td class="py-2 pr-4">{{ $result->submitted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="py-2 pr-4">{{ number_format((float) $result->raw_score, 0) }}/{{ $result->total_questions }}</td>
                                <td class="py-2 pr-4">{{ $result->band_score !== null ? number_format((float) $result->band_score, 1) : '—' }}</td>
                                <td class="py-2 pr-4">@include('student.listening.results.partials.result-status', ['result' => $result])</td>
                                <td class="py-2">
                                    @if ($result->status?->value === 'ready')
                                        <x-ui.button size="sm" href="{{ route('student.listening.results.show', $result) }}">View</x-ui.button>
                                    @elseif ($result->status?->value === 'pending')
                                        <x-ui.button size="sm" href="{{ route('student.listening.attempts.result', $result->attempt) }}">View</x-ui.button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $results->links() }}</div>
        @endif
    </x-ui.card>
</x-layouts.dashboard>
