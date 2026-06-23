<x-layouts.admin :title="$test->title.' Analytics'" :heading="$test->title" eyebrow="Reading Analytics" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Reading Analytics', 'href' => route('admin.reading-analytics.index')], ['label' => $test->title]]">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-neutral-900 dark:text-white">{{ $test->title }}</h2>
            <p class="text-sm aa-muted">{{ $summary['attempt_count'] }} scored attempts</p>
        </div>
        <x-ui.button href="{{ route('admin.reading-analytics.export', $test) }}" variant="outline">Download Report CSV</x-ui.button>
    </div>

    <div class="mb-6 grid gap-4 md:grid-cols-4">
        <x-ui.stat-card label="Average Accuracy" :value="$summary['average_accuracy'].'%'" />
        <x-ui.stat-card label="Average Time / Question" :value="gmdate('i:s', $summary['average_time_seconds'] ?: 0)" />
        <x-ui.stat-card label="Average Band" :value="number_format($summary['average_band'], 1)" />
        <x-ui.stat-card label="Skipped Questions" :value="$summary['total_skipped']" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card title="Band Distribution">
            @forelse ($summary['band_distribution'] as $band => $count)
                <div class="mb-3 flex items-center justify-between rounded-2xl bg-neutral-50 px-4 py-3 dark:bg-neutral-900">
                    <span>Band {{ $band }}</span>
                    <x-ui.badge tone="blue">{{ $count }} attempts</x-ui.badge>
                </div>
            @empty
                <x-ui.empty-state title="No band data">Score at least one attempt for this test.</x-ui.empty-state>
            @endforelse
        </x-ui.card>

        <x-ui.card title="Accuracy Heat Map">
            <x-admin.reading-heat-map :cells="$summary['heat_map']['cells'] ?? []" :legend="$summary['heat_map']['legend'] ?? []" />
        </x-ui.card>
    </div>

    <x-ui.card title="Recent Attempts" class="mt-6">
        <x-ui.table>
            <thead><tr class="text-left text-xs uppercase aa-muted"><th class="p-4">Student</th><th class="p-4">Band</th><th class="p-4">Accuracy</th><th class="p-4">Avg Time</th><th class="p-4">Skipped</th><th class="p-4">Actions</th></tr></thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                @forelse ($summary['recent_attempts'] as $attempt)
                    <tr>
                        <td class="p-4">{{ $attempt['student'] ?? 'Student' }}</td>
                        <td class="p-4">{{ number_format($attempt['band'], 1) }}</td>
                        <td class="p-4">{{ $attempt['accuracy_percent'] }}%</td>
                        <td class="p-4">{{ gmdate('i:s', $attempt['average_time_seconds'] ?: 0) }}</td>
                        <td class="p-4">{{ $attempt['skipped_count'] }}</td>
                        <td class="p-4"><x-ui.button href="{{ route('admin.reading-analytics.attempt', $attempt['uuid']) }}" size="sm" variant="outline">Details</x-ui.button></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-8"><x-ui.empty-state title="No attempts">Students have not submitted this reading test yet.</x-ui.empty-state></td></tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.card>
</x-layouts.admin>
