@props(['question' => null])

<div class="grid gap-3 md:grid-cols-4">
    <x-ui.input
        name="reference_paragraph"
        label="Reference Paragraph"
        placeholder="A"
        :value="$question?->reference_paragraph ?? $question?->paragraph_reference"
    />
    <x-ui.input
        name="reference_start_offset"
        type="number"
        min="0"
        label="Reference Start"
        :value="$question?->reference_start_offset"
    />
    <x-ui.input
        name="reference_end_offset"
        type="number"
        min="0"
        label="Reference End"
        :value="$question?->reference_end_offset"
    />
</div>
