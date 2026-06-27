<form method="GET" class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <x-ui.input name="search" label="Search" :value="$filters['search'] ?? ''" placeholder="Title, text, passage..." />
    <x-ui.select name="audio_id" label="Audio">
        <option value="">All audio</option>
        @foreach ($audios as $audio)
            <option value="{{ $audio->id }}" @selected((string) ($filters['audio_id'] ?? '') === (string) $audio->id)>{{ $audio->original_name }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.select name="visibility" label="Visibility">
        <option value="">All visibility</option>
        @foreach ($visibilities as $visibility)
            <option value="{{ $visibility->value }}" @selected(($filters['visibility'] ?? '') === $visibility->value)>{{ $visibility->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.select name="is_official" label="Official">
        <option value="">All</option>
        <option value="1" @selected(($filters['is_official'] ?? '') === '1')>Official</option>
        <option value="0" @selected(($filters['is_official'] ?? '') === '0')>Custom</option>
    </x-ui.select>
    <x-ui.select name="source_type" label="Source">
        <option value="">All sources</option>
        @foreach ($sourceTypes as $sourceType)
            <option value="{{ $sourceType->value }}" @selected(($filters['source_type'] ?? '') === $sourceType->value)>{{ $sourceType->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.input name="language" label="Language" :value="$filters['language'] ?? ''" />
    <x-ui.input type="date" name="date_from" label="Date From" :value="$filters['date_from'] ?? ''" />
    <x-ui.input type="date" name="date_to" label="Date To" :value="$filters['date_to'] ?? ''" />
    <div class="flex items-end gap-2 md:col-span-2 xl:col-span-4">
        <x-ui.button type="submit">Filter</x-ui.button>
        <x-ui.button href="{{ route($routePrefix.'.index') }}" variant="outline">Reset</x-ui.button>
        <x-ui.button variant="outline" disabled title="Import coming in a later volume">Import (Soon)</x-ui.button>
    </div>
</form>
