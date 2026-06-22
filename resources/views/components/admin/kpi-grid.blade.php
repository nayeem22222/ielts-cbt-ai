@props(['items' => [], 'columns' => 'grid-cols-2 xl:grid-cols-4'])

<div {{ $attributes->merge(['class' => 'grid gap-4 sm:grid-cols-2 '.$columns]) }} data-dashboard-kpis>
    @foreach ($items as $kpi)
        <x-ui.stat-card
            :label="$kpi['label']"
            :value="$kpi['value']"
            :change="$kpi['change'] ?? null"
            :icon="$kpi['icon']"
        />
    @endforeach
</div>
