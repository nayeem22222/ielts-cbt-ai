<div class="grid gap-4 md:grid-cols-2">
    <x-ui.input name="title" label="Title" :value="old('title', $listeningTest->title ?? '')" required class="md:col-span-2" />
    <x-ui.input name="slug" label="Slug" :value="old('slug', $listeningTest->slug ?? '')" help="Leave blank to auto-generate from title." />
    <x-ui.input name="test_code" label="Test Code" :value="old('test_code', $listeningTest->test_code ?? '')" help="Leave blank to auto-generate." />
    <x-ui.textarea name="description" label="Description" class="md:col-span-2" rows="3">{{ old('description', $listeningTest->description ?? '') }}</x-ui.textarea>
    <x-ui.select name="test_type" label="Test Type">
        @foreach ($testTypes as $type)
            <option value="{{ $type->value }}" @selected(old('test_type', $listeningTest->test_type?->value ?? 'academic') === $type->value)>{{ $type->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.select name="difficulty_level" label="Difficulty Level">
        @foreach ($difficultyLevels as $level)
            <option value="{{ $level->value }}" @selected(old('difficulty_level', $listeningTest->difficulty_level?->value ?? 'official') === $level->value)>{{ $level->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.select name="status" label="Status">
        @foreach ($statuses as $status)
            <option value="{{ $status->value }}" @selected(old('status', $listeningTest->status?->value ?? 'draft') === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.input name="duration_minutes" type="number" min="1" max="180" label="Duration (minutes)" :value="old('duration_minutes', $listeningTest->duration_minutes ?? 30)" required />
    <x-ui.input name="transfer_time_minutes" type="number" min="0" max="30" label="Transfer Time (minutes)" :value="old('transfer_time_minutes', $listeningTest->transfer_time_minutes ?? 10)" />
    <x-ui.textarea name="instructions" label="Instructions" class="md:col-span-2" rows="4">{{ old('instructions', $listeningTest->instructions ?? '') }}</x-ui.textarea>
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $listeningTest->is_active ?? false))>
        <span>Active</span>
    </label>
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $listeningTest->is_featured ?? false))>
        <span>Featured</span>
    </label>
</div>
<div class="mt-6 flex gap-3">
    <x-ui.button type="submit">{{ $submitLabel }}</x-ui.button>
    <x-ui.button href="{{ route($routePrefix.'.index') }}" variant="outline">Cancel</x-ui.button>
</div>
