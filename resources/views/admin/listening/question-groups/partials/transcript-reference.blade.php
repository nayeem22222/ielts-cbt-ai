@php
    $json = old('transcript_reference', $group->transcript_reference ? json_encode($group->transcript_reference, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '');
    if (is_array($json)) { $json = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); }
@endphp
<details class="md:col-span-2 rounded-xl border border-neutral-200 p-3 dark:border-neutral-800">
    <summary class="cursor-pointer text-sm font-medium">Transcript reference (optional)</summary>
    <div class="mt-3">
        <x-ui.textarea name="transcript_reference" label="Transcript Reference (JSON)" rows="4" class="font-mono text-sm">{{ $json }}</x-ui.textarea>
        <p class="mt-1 text-xs aa-muted">Link this group to transcript lines. Attach a transcript on the section page first.</p>
    </div>
</details>
