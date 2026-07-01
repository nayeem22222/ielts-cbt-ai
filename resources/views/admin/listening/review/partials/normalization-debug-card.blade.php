<x-ui.card title="Normalization Debug">
    <pre class="overflow-x-auto text-xs">{{ json_encode($item['admin_meta']['normalization_steps'] ?? null, JSON_PRETTY_PRINT) }}</pre>
    <pre class="mt-3 overflow-x-auto text-xs">{{ json_encode($item['admin_meta']['evaluator_meta'] ?? null, JSON_PRETTY_PRINT) }}</pre>
</x-ui.card>
