<x-layouts.admin title="Attempt Analytics" heading="Attempt Analytics" eyebrow="Reading Analytics" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Reading Analytics', 'href' => route('admin.reading-analytics.index')], ['label' => $analytics->test->title, 'href' => route('admin.reading-analytics.show', $analytics->test)], ['label' => 'Attempt']]">
    <div class="mb-6 grid gap-4 md:grid-cols-4">
        <x-ui.stat-card label="Band" :value="number_format((float) $analytics->band, 1)" />
        <x-ui.stat-card label="Accuracy" :value="$analytics->accuracy_percent.'%'" />
        <x-ui.stat-card label="Average Time / Question" :value="gmdate('i:s', $analytics->average_time_seconds ?: 0)" />
        <x-ui.stat-card label="Skipped Questions" :value="$analytics->skipped_count" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card title="Time Per Question">
            <x-ui.table>
                <thead><tr class="text-left text-xs uppercase aa-muted"><th class="p-4">Q#</th><th class="p-4">Type</th><th class="p-4">Time</th><th class="p-4">Accuracy</th><th class="p-4">Status</th></tr></thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    @foreach ($analytics->time_per_question ?? [] as $item)
                        <tr>
                            <td class="p-4 font-semibold">{{ $item['question_number'] }}</td>
                            <td class="p-4 text-sm aa-muted">{{ str_replace('_', ' ', $item['question_type']) }}</td>
                            <td class="p-4">{{ gmdate('i:s', $item['time_spent_seconds'] ?? 0) }}</td>
                            <td class="p-4">{{ $item['accuracy_percent'] }}%</td>
                            <td class="p-4">
                                @if ($item['is_skipped'] ?? false)
                                    <x-ui.badge tone="amber">Skipped</x-ui.badge>
                                @elseif ($item['is_correct'] ?? false)
                                    <x-ui.badge tone="green">Correct</x-ui.badge>
                                @else
                                    <x-ui.badge tone="red">Incorrect</x-ui.badge>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        </x-ui.card>

        <x-ui.card title="Attempt Heat Map">
            <x-admin.reading-heat-map :cells="$analytics->heat_map['cells'] ?? []" :legend="$analytics->heat_map['legend'] ?? []" />
        </x-ui.card>
    </div>
</x-layouts.admin>
