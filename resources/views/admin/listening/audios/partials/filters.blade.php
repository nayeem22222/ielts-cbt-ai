<form method="GET" action="{{ route($routePrefix.'.index') }}" class="mb-4 grid gap-3 md:grid-cols-4">
    <x-ui.input name="search" label="Search" :value="$filters['search'] ?? ''" />
    <x-ui.select name="processing_status" label="Processing Status">
        <option value="">All</option>
        @foreach (\App\Enums\Listening\ListeningAudioProcessingStatus::cases() as $status)
            <option value="{{ $status->value }}" @selected(($filters['processing_status'] ?? '') === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.select name="validation_status" label="Validation Status">
        <option value="">All</option>
        @foreach (\App\Enums\Listening\ListeningAudioValidationStatus::cases() as $status)
            <option value="{{ $status->value }}" @selected(($filters['validation_status'] ?? '') === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.input name="format" label="Format" :value="$filters['format'] ?? ''" />
    <x-ui.input name="date_from" type="date" label="Date From" :value="$filters['date_from'] ?? ''" />
    <x-ui.input name="date_to" type="date" label="Date To" :value="$filters['date_to'] ?? ''" />
    <div class="flex items-end gap-2 md:col-span-2">
        <x-ui.button type="submit">Filter</x-ui.button>
        <x-ui.button href="{{ route($routePrefix.'.index') }}" variant="outline">Reset</x-ui.button>
    </div>
</form>
