<x-ui.card title="Answer Summary">
    @if (empty($questionSummary))
        <p class="text-sm aa-muted">No answer summary available.</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b text-left">
                        <th class="py-2 pr-3">Q#</th>
                        <th class="py-2 pr-3">Your answer</th>
                        <th class="py-2 pr-3">Result</th>
                        <th class="py-2 pr-3">Marks</th>
                        @if ($showCorrectAnswers ?? false)
                            <th class="py-2 pr-3">Correct answer</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($questionSummary as $row)
                        <tr class="border-b">
                            <td class="py-2 pr-3">{{ $row['question_number'] ?? '—' }}</td>
                            <td class="py-2 pr-3">{{ $row['student_answer'] ?? '—' }}</td>
                            <td class="py-2 pr-3">
                                @if (($row['is_correct'] ?? false) === true)
                                    <span class="text-green-700">Correct</span>
                                @elseif (($row['match_status'] ?? '') === 'unanswered')
                                    <span class="aa-muted">Unanswered</span>
                                @else
                                    <span class="text-red-700">Incorrect</span>
                                @endif
                            </td>
                            <td class="py-2 pr-3">{{ $row['marks_awarded'] ?? 0 }}/{{ $row['marks_available'] ?? 1 }}</td>
                            @if ($showCorrectAnswers ?? false)
                                <td class="py-2 pr-3">{{ $row['correct_answer'] ?? '—' }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-ui.card>
