@if (! empty($preview))
    <x-ui.card title="Admin Preview" class="mt-6">
        @include($preview['preview_partial'] ?? 'admin.listening.question-types.partials.type-preview', ['preview' => $preview, 'group' => $group])
    </x-ui.card>
@endif
