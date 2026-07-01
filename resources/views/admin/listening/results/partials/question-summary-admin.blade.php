<x-ui.card title="Question Summary (Admin)">
    @if (empty($questionSummary))
        <p class="text-sm aa-muted">No question summary available.</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b text-left">
                        <th class="py-2 pr-2">Q#</th>
                        <th class="py-2 pr-2">Student</th>
                        <th class="py-2 pr-2">Normalized</th>
                        <th class="py-2 pr-2">Correct</th>
                        <th class="py-2 pr-2">Accepted</th>
                        <th class="py-2 pr-2">Match</th>
                        <th class="py-2 pr-2">Reason</th>
                        <th class="py-2 pr-2">Marks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($questionSummary as $row)
                        <tr class="border-b align-top">
                            <td class="py-2 pr-2">{{ $row['question_number'] ?? '—' }}</td>
                            <td class="py-2 pr-2">{{ $row['student_answer'] ?? '—' }}</td>
                            <td class="py-2 pr-2">{{ $row['normalized_answer'] ?? '—' }}</td>
                            <td class="py-2 pr-2">{{ $row['correct_answer'] ?? '—' }}</td>
                            <td class="py-2 pr-2">
                                @if (!empty($row['accepted_answers']))
                                    <pre class="whitespace-pre-wrap text-xs">{{ json_encode($row['accepted_answers'], JSON_PRETTY_PRINT) }}</pre>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-2 pr-2">{{ $row['match_status'] ?? '—' }}</td>
                            <td class="py-2 pr-2">{{ $row['match_reason'] ?? '—' }}</td>
                            <td class="py-2 pr-2">{{ $row['marks_awarded'] ?? 0 }}/{{ $row['marks_available'] ?? 1 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-ui.card>
