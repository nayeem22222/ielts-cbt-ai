<x-ui.card title="Processing Log">
    <dl class="grid gap-3 text-sm">
        <div><dt class="aa-muted">Started</dt><dd>{{ $audio->processing_started_at?->format('Y-m-d H:i:s') ?? '—' }}</dd></div>
        <div><dt class="aa-muted">Finished</dt><dd>{{ $audio->processing_finished_at?->format('Y-m-d H:i:s') ?? '—' }}</dd></div>
        <div><dt class="aa-muted">Retry Count</dt><dd>{{ $audio->retry_count ?? 0 }}</dd></div>
        @if ($audio->processing_error)
            <div><dt class="aa-muted">Processing Error</dt><dd class="text-danger-600">{{ $audio->processing_error }}</dd></div>
        @endif
        @if (is_array($audio->validation_errors) && $audio->validation_errors !== [])
            <div>
                <dt class="aa-muted">Validation Errors</dt>
                <dd>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach ($audio->validation_errors as $error)
                            <li>{{ is_array($error) ? ($error['message'] ?? json_encode($error)) : $error }}</li>
                        @endforeach
                    </ul>
                </dd>
            </div>
        @endif
    </dl>
</x-ui.card>
