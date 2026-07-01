<x-ui.card>
    <p class="text-sm aa-muted">{{ $test?->title ?? 'Listening Test' }} · Result {{ $result->result_code }}</p>
    <p class="mt-1 text-sm">Band {{ $result->band_score !== null ? number_format((float) $result->band_score, 1) : '—' }} · Score {{ number_format((float) $result->raw_score, 0) }}/{{ $result->total_questions }}</p>
</x-ui.card>
