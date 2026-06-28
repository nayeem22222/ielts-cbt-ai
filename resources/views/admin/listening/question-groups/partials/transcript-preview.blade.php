@if ($group->transcript_reference)
    <x-ui.card title="Transcript Reference" class="mt-6">
        <pre class="overflow-x-auto text-sm">{{ json_encode($group->transcript_reference, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </x-ui.card>
@endif
