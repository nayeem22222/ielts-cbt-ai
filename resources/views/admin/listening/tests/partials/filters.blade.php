<form method="GET" action="{{ route($routePrefix.'.index') }}" class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <x-ui.input name="search" label="Search" :value="$filters['search'] ?? ''" placeholder="Title, slug, code, description" />
    <x-ui.select name="status" label="Status">
        <option value="">All statuses</option>
        @foreach ($statuses as $status)
            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.select name="test_type" label="Test Type">
        <option value="">All types</option>
        @foreach ($testTypes as $type)
            <option value="{{ $type->value }}" @selected(($filters['test_type'] ?? '') === $type->value)>{{ $type->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.select name="difficulty_level" label="Difficulty">
        <option value="">All levels</option>
        @foreach ($difficultyLevels as $level)
            <option value="{{ $level->value }}" @selected(($filters['difficulty_level'] ?? '') === $level->value)>{{ $level->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.select name="is_active" label="Active">
        <option value="">Any</option>
        <option value="1" @selected(($filters['is_active'] ?? '') === '1' || ($filters['is_active'] ?? '') === 1 || ($filters['is_active'] ?? '') === true)>Active</option>
        <option value="0" @selected(($filters['is_active'] ?? '') === '0' || ($filters['is_active'] ?? '') === 0 || ($filters['is_active'] ?? '') === false)>Inactive</option>
    </x-ui.select>
    <x-ui.select name="is_featured" label="Featured">
        <option value="">Any</option>
        <option value="1" @selected(($filters['is_featured'] ?? '') === '1')>Featured</option>
        <option value="0" @selected(($filters['is_featured'] ?? '') === '0')>Not featured</option>
    </x-ui.select>
    <x-ui.input name="date_from" type="date" label="Date From" :value="$filters['date_from'] ?? ''" />
    <x-ui.input name="date_to" type="date" label="Date To" :value="$filters['date_to'] ?? ''" />
    <x-ui.select name="trashed" label="Deleted">
        <option value="">Without deleted</option>
        <option value="with" @selected(($filters['trashed'] ?? '') === 'with')>With deleted</option>
        <option value="only" @selected(($filters['trashed'] ?? '') === 'only')>Only deleted</option>
    </x-ui.select>
    <div class="flex items-end gap-2 md:col-span-2 xl:col-span-4">
        <x-ui.button type="submit">Apply Filters</x-ui.button>
        <x-ui.button href="{{ route($routePrefix.'.index') }}" variant="outline">Reset</x-ui.button>
    </div>
</form>
