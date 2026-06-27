<div
    class="grid gap-4 md:grid-cols-2"
    x-data="{
        sectionNumber: '{{ old('section_number', $section->section_number ?? '') }}',
        rangeMap: @js(collect($sectionRangeMap)->map(fn ($item) => ['start' => $item['start'], 'end' => $item['end']])->all())
    }"
>
    <x-ui.select name="section_number" label="Section Number" x-model="sectionNumber" required>
        <option value="">Select section</option>
        @foreach ($sectionRangeMap as $number => $config)
            @if (in_array($number, $availableSectionNumbers ?? array_keys($sectionRangeMap), true))
                <option value="{{ $number }}" @selected((string) old('section_number', $section->section_number ?? '') === (string) $number)>Section {{ $number }} (Q{{ $config['start'] }}–Q{{ $config['end'] }})</option>
            @endif
        @endforeach
    </x-ui.select>
    <div>
        <p class="mb-1 text-sm font-medium text-neutral-700 dark:text-neutral-200">Official Question Range</p>
        <p class="rounded-xl border border-neutral-200 px-3 py-2 text-sm dark:border-neutral-800" x-text="rangeMap[sectionNumber] ? `Q${rangeMap[sectionNumber].start}–Q${rangeMap[sectionNumber].end}` : 'Select a section number'"></p>
    </div>
    <x-ui.input name="title" label="Title" :value="old('title', $section->title ?? '')" class="md:col-span-2" />
    @php
        $defaultSectionType = old(
            'section_type',
            $section->section_type?->value
                ?? (isset($section->section_number, $sectionRangeMap[$section->section_number])
                    ? $sectionRangeMap[$section->section_number]['default_type']->value
                    : '')
        );
    @endphp
    <x-ui.select name="section_type" label="Section Type">
        @foreach ($sectionTypes as $type)
            <option value="{{ $type->value }}" @selected($defaultSectionType === $type->value)>{{ $type->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.input name="display_order" type="number" min="1" max="4" label="Display Order" :value="old('display_order', $section->display_order ?? '')" />
    <x-ui.textarea name="instruction" label="Instruction" class="md:col-span-2" rows="4">{{ old('instruction', $section->instruction ?? '') }}</x-ui.textarea>
    @include('admin.listening.sections.partials.audio-selector')
    @include('admin.listening.sections.partials.transcript-selector')
    <x-ui.input name="duration_seconds" type="number" min="1" max="3600" label="Duration (seconds)" :value="old('duration_seconds', $section->duration_seconds ?? '')" />
    <x-ui.input name="preparation_seconds" type="number" min="0" max="300" label="Preparation (seconds)" :value="old('preparation_seconds', $section->preparation_seconds ?? '')" />
    <label class="flex items-center gap-2 text-sm md:col-span-2">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $section->is_active ?? true))>
        <span>Active</span>
    </label>
</div>
<div class="mt-6 flex gap-3">
    <x-ui.button type="submit">{{ $submitLabel }}</x-ui.button>
    <x-ui.button href="{{ route($sectionsRoutePrefix.'.index', $listeningTest) }}" variant="outline">Cancel</x-ui.button>
</div>
