<div
    x-show="liveDetectError || (removedCandidates.length > 0)"
    x-cloak
    class="mb-4 rounded-2xl border px-4 py-3 text-sm"
    :class="liveDetectError ? 'border-red-300 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200' : 'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-100'"
>
    <template x-if="liveDetectError">
        <p><span class="font-semibold">Validation:</span> <span x-text="liveDetectError"></span></p>
    </template>
    <template x-if="!liveDetectError && removedCandidates.length > 0">
        <p>
            <span class="font-semibold">Orphan warning:</span>
            Removing placeholders will affect questions
            <span class="font-semibold" x-text="removedCandidates.join(', ')"></span>.
            Confirm on save to delete linked answers.
        </p>
    </template>
</div>

<div x-show="detectedPlaceholders.length > 0" class="mb-4 rounded-2xl border border-brand-200 bg-brand-50/60 px-4 py-3 text-sm dark:border-brand-800 dark:bg-brand-950/20">
    <p class="font-medium text-brand-800 dark:text-brand-200">Live detection</p>
    <p class="mt-1 aa-muted">
        Placeholders:
        <span class="font-semibold text-neutral-900 dark:text-white" x-text="detectedPlaceholders.map((item) => item.question_number + (item.label ? ':' + item.label : '')).join(', ') || '—'"></span>
    </p>
</div>
