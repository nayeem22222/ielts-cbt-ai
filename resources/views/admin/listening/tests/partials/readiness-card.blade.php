<x-ui.card title="Readiness Summary">
    <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <dt class="text-xs uppercase aa-muted">Sections</dt>
            <dd class="text-lg font-semibold">{{ $readiness['sections_count'] }}/{{ $totalSections }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase aa-muted">Questions</dt>
            <dd class="text-lg font-semibold">{{ $readiness['questions_count'] }}/{{ $totalQuestions }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase aa-muted">Question Groups</dt>
            <dd class="text-lg font-semibold">{{ $readiness['groups_count'] }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase aa-muted">Sections With Audio</dt>
            <dd class="text-lg font-semibold">{{ $readiness['sections_with_audio'] }}/{{ $totalSections }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase aa-muted">Settings</dt>
            <dd class="text-lg font-semibold">{{ $readiness['has_settings'] ? 'Configured' : 'Missing' }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase aa-muted">Publish Ready</dt>
            <dd>
                <x-ui.badge :tone="$readiness['is_publish_ready'] ? 'green' : 'amber'">
                    {{ $readiness['is_publish_ready'] ? 'Yes' : 'No' }}
                </x-ui.badge>
            </dd>
        </div>
    </dl>

    @if (! empty($readiness['missing']))
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
            <p class="mb-2 font-semibold">Missing requirements</p>
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($readiness['missing'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</x-ui.card>
