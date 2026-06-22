<x-layouts.admin title="Packages" heading="Packages" eyebrow="Commerce" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Packages']]">
    <div class="mb-6 flex justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-neutral-900 dark:text-white">Packages</h2>
            <p class="text-sm aa-muted">Manage subscription plans, module access, attempt limits, pricing, and discounts.</p>
        </div>
        @can('create', \App\Models\Package::class)
            <x-ui.button href="{{ route($routePrefix.'.create') }}">Add Package</x-ui.button>
        @endcan
    </div>

    <x-ui.card title="Package Directory">
        <x-admin.crud-toolbar :route-prefix="$routePrefix" :filters="$filters" :sort="$sort" :direction="$direction" :definition="$definition" :statuses="$statuses" show-status-filter>
            <x-slot:customFilters>
                <x-ui.select name="billing_interval" label="Billing">
                    <option value="">All intervals</option>
                    @foreach ($intervals as $interval)
                        <option value="{{ $interval->value }}" @selected(($filters['billing_interval'] ?? '') === $interval->value)>{{ $interval->label() }}</option>
                    @endforeach
                </x-ui.select>
            </x-slot:customFilters>
        </x-admin.crud-toolbar>

        <x-ui.table>
            <thead>
                <tr class="text-left text-xs uppercase aa-muted">
                    <th class="p-4"><input type="checkbox" data-crud-select-all></th>
                    <th class="p-4">Name</th>
                    <th class="p-4">Modules</th>
                    <th class="p-4">Duration</th>
                    <th class="p-4">Price</th>
                    <th class="p-4">Discount</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                @forelse ($records as $record)
                    <tr>
                        <td class="p-4"><input type="checkbox" data-crud-row-checkbox value="{{ $record->id }}"></td>
                        <td class="p-4">
                            <div class="font-medium text-neutral-900 dark:text-white">{{ $record->name }}</div>
                            <div class="text-xs aa-muted">{{ $record->billing_interval->label() }}</div>
                        </td>
                        <td class="p-4">
                            <div class="flex flex-wrap gap-1">
                                @foreach ($record->enabledModules() as $module)
                                    <x-ui.badge tone="blue">{{ ucfirst($module) }}</x-ui.badge>
                                @endforeach
                            </div>
                        </td>
                        <td class="p-4 text-sm">{{ $record->duration_days ? $record->duration_days.' days' : 'Unlimited' }}</td>
                        <td class="p-4">
                            <div class="font-medium">{{ $record->currency }} {{ number_format($record->effectivePrice(), 2) }}</div>
                            @if ($record->discount_type->value !== 'none')
                                <div class="text-xs aa-muted line-through">{{ $record->currency }} {{ number_format((float) $record->price, 2) }}</div>
                            @endif
                        </td>
                        <td class="p-4 text-sm aa-muted">
                            @if ($record->discount_type->value === 'percent')
                                {{ $record->discount_value }}%
                            @elseif ($record->discount_type->value === 'fixed')
                                {{ $record->currency }} {{ number_format((float) $record->discount_value, 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="p-4"><x-ui.badge :tone="$record->status->value === 'active' ? 'green' : 'amber'">{{ $record->status->label() }}</x-ui.badge></td>
                        <td class="p-4">
                            <div class="flex gap-2">
                                @can('update', $record)
                                    <x-ui.button href="{{ route($routePrefix.'.edit', $record) }}" size="sm" variant="outline">Edit</x-ui.button>
                                @endcan
                                @can('delete', $record)
                                    <form method="POST" action="{{ route($routePrefix.'.destroy', $record) }}" onsubmit="return confirm('Delete this package?')">
                                        @csrf @method('DELETE')
                                        <x-ui.button type="submit" size="sm" variant="danger">Delete</x-ui.button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="p-8"><x-ui.empty-state title="No packages">Create a package plan to sell course access.</x-ui.empty-state></td></tr>
                @endforelse
            </tbody>
        </x-ui.table>
        <div class="mt-4">{{ $records->links() }}</div>
    </x-ui.card>
</x-layouts.admin>
