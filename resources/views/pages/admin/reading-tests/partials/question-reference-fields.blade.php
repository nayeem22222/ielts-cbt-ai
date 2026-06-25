@props(['question' => null])

@php
    $resolvedType = old('reference_type', $question?->reference_type);

    if (! $resolvedType) {
        if ($question?->reference_start_offset !== null && $question?->reference_end_offset !== null) {
            $resolvedType = 'offset';
        } else {
            $resolvedType = 'phrase';
        }
    }
@endphp

<div class="space-y-3 rounded-xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-900/40">
    <p class="text-sm font-semibold text-neutral-800 dark:text-neutral-100">Passage Reference (Review Highlights)</p>

    <x-ui.select name="reference_type" label="Reference Type">
        <option value="" @selected($resolvedType === '')>Auto-detect</option>
        <option value="phrase" @selected($resolvedType === 'phrase')>Phrase (recommended)</option>
        <option value="sentence" @selected($resolvedType === 'sentence')>Sentence</option>
        <option value="offset" @selected($resolvedType === 'offset')>Character offset (legacy)</option>
    </x-ui.select>

    <x-ui.textarea
        name="reference_phrase"
        label="Reference Phrase"
        rows="2"
        placeholder="Paste the exact words from the passage..."
    >{{ old('reference_phrase', $question?->reference_phrase) }}</x-ui.textarea>

    <x-ui.textarea
        name="reference_sentence"
        label="Reference Sentence"
        rows="2"
        placeholder="Paste the full sentence from the passage..."
    >{{ old('reference_sentence', $question?->reference_sentence) }}</x-ui.textarea>

    <div class="grid gap-3 md:grid-cols-3">
        <x-ui.input
            name="reference_paragraph"
            label="Reference Paragraph (optional)"
            placeholder="A"
            :value="old('reference_paragraph', $question?->reference_paragraph ?? $question?->paragraph_reference)"
        />
        <x-ui.input
            name="reference_start_offset"
            type="number"
            min="0"
            label="Reference Start"
            :value="old('reference_start_offset', $question?->reference_start_offset)"
        />
        <x-ui.input
            name="reference_end_offset"
            type="number"
            min="0"
            label="Reference End"
            :value="old('reference_end_offset', $question?->reference_end_offset)"
        />
    </div>

    <p class="text-xs text-neutral-500">
        Phrase mode searches the passage (or paragraph when set). Offset mode uses character positions inside the paragraph body.
    </p>
</div>
