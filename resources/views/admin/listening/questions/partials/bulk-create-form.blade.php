@php
    $bulkGroup = $bulkGroup ?? $group ?? null;
    $start = (int) ($bulkGroup->start_question_number ?? 0);
    $end = (int) ($bulkGroup->end_question_number ?? 0);
    $confirm = "Create placeholder questions for Q{$start}–Q{$end}? Existing question numbers will be skipped.";
    $size = $size ?? 'md';
    $variant = $variant ?? 'outline';
@endphp
@if ($bulkGroup)
    @can('bulkCreate', [\App\Models\Listening\ListeningQuestion::class, $bulkGroup])
        <form
            method="POST"
            action="{{ route($questionsRoutePrefix.'.bulk-create', [$listeningTest, $section, $bulkGroup]) }}"
            onsubmit="return confirm(@js($confirm))"
        >
            @csrf
            <x-ui.button type="submit" :size="$size" :variant="$variant">Bulk Create Questions</x-ui.button>
        </form>
    @endcan
@endif
