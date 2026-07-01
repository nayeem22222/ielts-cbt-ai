<x-ui.card title="Question Type Breakdown">
    @if (empty($questionTypeBreakdown))
        <p class="text-sm aa-muted">No question type breakdown available.</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b text-left">
                        <th class="py-2 pr-3">Type</th>
                        <th class="py-2 pr-3">Total</th>
                        <th class="py-2 pr-3">Correct</th>
                        <th class="py-2 pr-3">Incorrect</th>
                        <th class="py-2 pr-3">%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($questionTypeBreakdown as $row)
                        <tr class="border-b">
                            <td class="py-2 pr-3">{{ $row['label'] ?? $row['question_type'] ?? '—' }}</td>
                            <td class="py-2 pr-3">{{ $row['total'] ?? 0 }}</td>
                            <td class="py-2 pr-3">{{ $row['correct'] ?? 0 }}</td>
                            <td class="py-2 pr-3">{{ $row['incorrect'] ?? 0 }}</td>
                            <td class="py-2 pr-3">{{ $row['percentage'] ?? 0 }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-ui.card>
