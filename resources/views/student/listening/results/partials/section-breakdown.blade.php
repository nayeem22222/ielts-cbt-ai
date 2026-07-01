<x-ui.card title="Section Breakdown">
    @if (empty($sectionBreakdown))
        <p class="text-sm aa-muted">No section breakdown available.</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b text-left">
                        <th class="py-2 pr-3">Section</th>
                        <th class="py-2 pr-3">Range</th>
                        <th class="py-2 pr-3">Correct</th>
                        <th class="py-2 pr-3">Incorrect</th>
                        <th class="py-2 pr-3">Unanswered</th>
                        <th class="py-2 pr-3">%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sectionBreakdown as $row)
                        <tr class="border-b">
                            <td class="py-2 pr-3">{{ $row['section_number'] ?? '—' }}</td>
                            <td class="py-2 pr-3">{{ $row['question_range'] ?? '—' }}</td>
                            <td class="py-2 pr-3">{{ $row['correct'] ?? 0 }}</td>
                            <td class="py-2 pr-3">{{ $row['incorrect'] ?? 0 }}</td>
                            <td class="py-2 pr-3">{{ $row['unanswered'] ?? 0 }}</td>
                            <td class="py-2 pr-3">{{ $row['percentage'] ?? 0 }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-ui.card>
