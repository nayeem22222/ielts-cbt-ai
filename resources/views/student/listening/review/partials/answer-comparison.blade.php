<x-ui.card title="Your Answer">
    <dl class="grid gap-2 text-sm">
        <div><dt class="aa-muted inline">Question:</dt> <dd class="inline font-medium">{{ $item['question_number'] }}</dd></div>
        <div><dt class="aa-muted inline">Your answer:</dt> <dd class="inline font-medium">{{ $item['student_answer'] ?? '—' }}</dd></div>
        <div><dt class="aa-muted inline">Status:</dt> <dd class="inline font-medium">{{ ucfirst((string) ($item['match_status'] ?? '—')) }}</dd></div>
        <div><dt class="aa-muted inline">Marks:</dt> <dd class="inline font-medium">{{ $item['marks_awarded'] ?? 0 }}/{{ $item['marks_available'] ?? 1 }}</dd></div>
        @if (!empty($item['correct_answer']))
            <div><dt class="aa-muted inline">Correct answer:</dt> <dd class="inline font-medium">{{ $item['correct_answer'] }}</dd></div>
        @endif
    </dl>
</x-ui.card>
