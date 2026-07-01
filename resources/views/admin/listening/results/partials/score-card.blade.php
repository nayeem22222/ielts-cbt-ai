<x-ui.card title="Score">
    <dl class="grid gap-2 text-sm sm:grid-cols-2">
        <div><dt class="aa-muted inline">Raw score:</dt> <dd class="inline font-medium">{{ number_format((float) $result->raw_score, 2) }}/{{ $result->total_questions }}</dd></div>
        <div><dt class="aa-muted inline">Band:</dt> <dd class="inline font-medium">{{ $result->band_score !== null ? number_format((float) $result->band_score, 1) : '—' }}</dd></div>
        <div><dt class="aa-muted inline">Correct:</dt> <dd class="inline font-medium">{{ number_format((float) $result->total_correct, 2) }}</dd></div>
        <div><dt class="aa-muted inline">Incorrect:</dt> <dd class="inline font-medium">{{ number_format((float) $result->total_incorrect, 2) }}</dd></div>
        <div><dt class="aa-muted inline">Unanswered:</dt> <dd class="inline font-medium">{{ $result->total_unanswered }}</dd></div>
        <div><dt class="aa-muted inline">Result code:</dt> <dd class="inline font-medium">{{ $result->result_code ?? '—' }}</dd></div>
        <div><dt class="aa-muted inline">Result status:</dt> <dd class="inline font-medium">{{ $result->status?->label() ?? '—' }}</dd></div>
        <div><dt class="aa-muted inline">Visible to student:</dt> <dd class="inline font-medium">{{ $result->is_visible_to_student ? 'Yes' : 'No' }}</dd></div>
    </dl>
</x-ui.card>
