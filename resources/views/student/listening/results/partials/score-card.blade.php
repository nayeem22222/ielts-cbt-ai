<x-ui.card title="Score Summary">
    <dl class="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <dt class="aa-muted">Submitted</dt>
            <dd class="font-medium">{{ $result->submitted_at?->format('Y-m-d H:i') ?? '—' }}</dd>
        </div>
        <div>
            <dt class="aa-muted">Raw score</dt>
            <dd class="font-medium">{{ number_format((float) $result->raw_score, 0) }}/{{ $result->total_questions }}</dd>
        </div>
        <div>
            <dt class="aa-muted">Correct</dt>
            <dd class="font-medium">{{ number_format((float) $result->total_correct, 0) }}</dd>
        </div>
        <div>
            <dt class="aa-muted">Incorrect</dt>
            <dd class="font-medium">{{ number_format((float) $result->total_incorrect, 0) }}</dd>
        </div>
        <div>
            <dt class="aa-muted">Unanswered</dt>
            <dd class="font-medium">{{ $result->total_unanswered }}</dd>
        </div>
        @if ($result->result_code)
            <div>
                <dt class="aa-muted">Result code</dt>
                <dd class="font-medium">{{ $result->result_code }}</dd>
            </div>
        @endif
    </dl>
</x-ui.card>
