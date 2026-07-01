<x-ui.card title="Answer Debug">
    <dl class="grid gap-2 text-sm">
        <div><dt class="aa-muted">Student snapshot</dt><dd><pre class="text-xs">{{ json_encode($item['student_answer_snapshot'] ?? null, JSON_PRETTY_PRINT) }}</pre></dd></div>
        <div><dt class="aa-muted">Correct snapshot</dt><dd><pre class="text-xs">{{ json_encode($item['correct_answer_snapshot'] ?? null, JSON_PRETTY_PRINT) }}</pre></dd></div>
        <div><dt class="aa-muted">Accepted answers</dt><dd><pre class="text-xs">{{ json_encode($item['accepted_answers_snapshot'] ?? null, JSON_PRETTY_PRINT) }}</pre></dd></div>
        <div><dt class="aa-muted">Normalized</dt><dd><pre class="text-xs">{{ json_encode($item['normalized_answer_snapshot'] ?? null, JSON_PRETTY_PRINT) }}</pre></dd></div>
        <div><dt class="aa-muted">Match</dt><dd>{{ $item['match_status'] }} — {{ $item['match_reason'] ?? '—' }}</dd></div>
    </dl>
</x-ui.card>
