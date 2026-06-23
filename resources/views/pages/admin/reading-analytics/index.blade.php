<x-layouts.admin title="Reading Analytics" heading="Reading Analytics" eyebrow="Test Builder" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Reading Analytics']]">
    <div class="mb-6 grid gap-4 md:grid-cols-4">
        <x-ui.stat-card label="Total Attempts" :value="$overview['total_attempts']" />
        <x-ui.stat-card label="Average Accuracy" :value="$overview['average_accuracy'].'%'" />
        <x-ui.stat-card label="Average Time / Q" :value="gmdate('i:s', $overview['average_time_seconds'] ?: 0)" />
        <x-ui.stat-card label="Band Buckets" :value="count($overview['band_distribution'] ?? [])" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card title="Band Distribution">
            @forelse ($overview['band_distribution'] ?? [] as $band => $count)
                <div class="mb-3">
                    <div class="mb-1 flex justify-between text-sm">
                        <span>Band {{ $band }}</span>
                        <span class="font-semibold">{{ $count }}</span>
                    </div>
                    <div class="h-2 rounded-full bg-neutral-100 dark:bg-neutral-800">
                        <div class="h-2 rounded-full bg-brand-500" style="width: {{ min(100, ($count / max(1, $overview['total_attempts'])) * 100) }}%"></div>
                    </div>
                </div>
            @empty
                <x-ui.empty-state title="No analytics yet">Complete reading attempts to populate band distribution.</x-ui.empty-state>
            @endforelse
        </x-ui.card>

        <x-ui.card title="Reading Tests">
            <x-ui.table>
                <thead><tr class="text-left text-xs uppercase aa-muted"><th class="p-4">Test</th><th class="p-4">Attempts</th><th class="p-4">Actions</th></tr></thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    @forelse ($overview['tests'] as $test)
                        <tr>
                            <td class="p-4 font-medium">{{ $test['title'] }}</td>
                            <td class="p-4">{{ $test['attempts'] }}</td>
                            <td class="p-4">
                                <x-ui.button href="{{ route('admin.reading-analytics.show', $test['id']) }}" size="sm" variant="outline">View</x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="p-8"><x-ui.empty-state title="No reading tests">Publish a reading test to begin collecting analytics.</x-ui.empty-state></td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>
    </div>
</x-layouts.admin>
