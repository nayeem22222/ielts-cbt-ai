<x-layouts.admin
    title="Admin Dashboard"
    heading="Admin Dashboard"
    eyebrow="Operations Control Center"
    :breadcrumbs="[
        ['label' => 'Dashboard'],
    ]"
>
    {{-- KPI Cards --}}
    <x-admin.kpi-grid :items="$kpis" class="mb-6" />

    <div class="grid gap-6 xl:grid-cols-12">
        {{-- Charts + Activities --}}
        <div class="space-y-6 xl:col-span-8">
            <div class="grid gap-6 lg:grid-cols-2">
                <x-ui.card title="User Growth" subtitle="New registrations over the last 6 months">
                    <x-admin.chart-bars :items="$charts['userGrowth']" />
                </x-ui.card>

                <x-ui.card title="Revenue Trend" subtitle="Paid order totals by month">
                    <x-admin.chart-bars :items="$charts['revenue']" />
                </x-ui.card>
            </div>

            <x-ui.card title="Module Completion" subtitle="Average completion rate by IELTS skill">
                <x-admin.chart-bars :items="$charts['completionRate']" :max="100" />
            </x-ui.card>

            <x-ui.card title="Recent Activities" subtitle="Latest authentication and platform events" data-dashboard-activities>
                <div class="space-y-3">
                    @forelse ($recentActivities as $activity)
                        <div class="flex items-start gap-3 rounded-2xl border border-neutral-200 px-4 py-3 dark:border-neutral-800">
                            <span @class([
                                'mt-1 h-2.5 w-2.5 shrink-0 rounded-full',
                                'bg-emerald-500' => $activity['tone'] === 'green',
                                'bg-red-500' => $activity['tone'] === 'red',
                                'bg-amber-500' => $activity['tone'] === 'amber',
                                'bg-brand-500' => $activity['tone'] === 'blue',
                            ])></span>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium">{{ $activity['title'] }}</p>
                                <p class="truncate text-sm aa-muted">{{ $activity['description'] }}</p>
                            </div>
                            <span class="shrink-0 text-xs aa-muted">{{ $activity['time'] }}</span>
                        </div>
                    @empty
                        <x-ui.empty-state title="No activity yet">Events will appear here as users interact with the platform.</x-ui.empty-state>
                    @endforelse
                </div>
            </x-ui.card>
        </div>

        {{-- Sidebar widgets --}}
        <div class="space-y-6 xl:col-span-4">
            <x-ui.card title="Quick Actions" subtitle="Common admin tasks" data-dashboard-quick-actions>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                    @foreach ($quickActions as $action)
                        <a
                            href="{{ $action['href'] }}"
                            class="flex items-start gap-3 rounded-2xl border border-neutral-200 px-4 py-3 transition hover:border-brand-500 hover:bg-brand-50/50 dark:border-neutral-800 dark:hover:bg-brand-500/10"
                        >
                            <span class="text-xl" aria-hidden="true">{{ $action['icon'] }}</span>
                            <span>
                                <span class="block font-semibold">{{ $action['label'] }}</span>
                                <span class="mt-1 block text-sm aa-muted">{{ $action['description'] }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </x-ui.card>

            <x-ui.card title="Notification Center" subtitle="Security and auth alerts" data-dashboard-notifications>
                <div class="space-y-3">
                    @forelse ($notifications as $notification)
                        <div @class([
                            'rounded-2xl border px-4 py-3',
                            'border-brand-200 bg-brand-50/60 dark:border-brand-500/30 dark:bg-brand-500/10' => $notification['unread'],
                            'border-neutral-200 dark:border-neutral-800' => ! $notification['unread'],
                        ])>
                            <div class="flex items-start justify-between gap-3">
                                <p class="font-medium">{{ $notification['title'] }}</p>
                                @if ($notification['unread'])
                                    <span class="rounded-full bg-brand-500 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">New</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm aa-muted">{{ $notification['body'] }}</p>
                            <p class="mt-2 text-xs aa-muted">{{ $notification['time'] }}</p>
                        </div>
                    @empty
                        <p class="text-sm aa-muted">No recent notifications.</p>
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card title="Server Health" subtitle="Runtime service status" data-dashboard-server-health>
                <div class="space-y-3">
                    @foreach ($serverHealth as $check)
                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-neutral-200 px-4 py-3 dark:border-neutral-800">
                            <div>
                                <p class="font-medium">{{ $check['label'] }}</p>
                                <p class="text-sm aa-muted">{{ $check['detail'] }}</p>
                            </div>
                            <x-ui.badge :tone="match ($check['status']) {
                                'healthy' => 'green',
                                'degraded' => 'amber',
                                default => 'red',
                            }">
                                {{ ucfirst($check['status']) }}
                            </x-ui.badge>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

            <x-ui.card title="AI Queue" subtitle="Evaluation job pipeline" data-dashboard-ai-queue>
                <div class="mb-4 grid grid-cols-2 gap-3">
                    @foreach ($aiQueue['summary'] as $status => $count)
                        <div class="rounded-2xl border border-neutral-200 px-3 py-3 text-center dark:border-neutral-800">
                            <p class="text-2xl font-bold">{{ number_format($count) }}</p>
                            <p class="mt-1 text-xs uppercase tracking-wide aa-muted">{{ $status }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="space-y-2">
                    <p class="text-sm font-semibold">Recent jobs</p>
                    @forelse ($aiQueue['recent'] as $job)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-neutral-200 px-3 py-2 text-sm dark:border-neutral-800">
                            <span class="font-medium">#{{ $job['id'] }}</span>
                            <x-ui.badge :tone="match ($job['status']) {
                                'completed' => 'green',
                                'failed', 'error' => 'red',
                                'processing' => 'amber',
                                default => 'blue',
                            }">{{ $job['status'] }}</x-ui.badge>
                            <span class="aa-muted">{{ $job['time'] }}</span>
                        </div>
                    @empty
                        <p class="text-sm aa-muted">No AI jobs in the queue yet.</p>
                    @endforelse
                </div>
            </x-ui.card>
        </div>
    </div>
</x-layouts.admin>
