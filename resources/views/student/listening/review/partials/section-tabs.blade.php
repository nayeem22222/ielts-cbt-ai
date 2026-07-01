<x-ui.card title="Sections">
    <div class="flex flex-wrap gap-2" data-review-section-tabs>
        <button type="button" class="rounded-full border px-3 py-1 text-sm" data-section-filter="all">All</button>
        @foreach ($sections as $section)
            <button type="button" class="rounded-full border px-3 py-1 text-sm" data-section-filter="{{ $section['section_number'] }}">
                Section {{ $section['section_number'] }} ({{ $section['count'] }})
            </button>
        @endforeach
    </div>
</x-ui.card>
