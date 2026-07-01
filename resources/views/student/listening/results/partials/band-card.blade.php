<x-ui.card title="Band Score" class="mt-6">
    <p class="text-3xl font-bold">{{ $result->band_score !== null ? number_format((float) $result->band_score, 1) : '—' }}</p>
</x-ui.card>
