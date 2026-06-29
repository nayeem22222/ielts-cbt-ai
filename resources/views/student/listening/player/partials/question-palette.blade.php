@php
    $paletteBySection = collect($payload['palette'] ?? [])->groupBy('section_number')->sortKeys();
@endphp

<aside id="listening-palette" class="w-full shrink-0 lg:w-64">
    <div class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold">Question palette</h2>
            <button type="button" id="listening-palette-toggle" class="text-xs font-medium text-brand-600 lg:hidden">Toggle</button>
        </div>
        <div class="mb-3 flex flex-wrap gap-2 text-[11px] aa-muted">
            <span>Answered: <strong id="listening-count-answered">0</strong></span>
            <span>Unanswered: <strong id="listening-count-unanswered">0</strong></span>
            <span>Flagged: <strong id="listening-count-flagged">0</strong></span>
        </div>
        <div id="listening-palette-grid" class="space-y-3">
            @foreach ($paletteBySection as $sectionNumber => $items)
                <div class="listening-palette-section" data-section="{{ $sectionNumber }}">
                    <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-neutral-500">Part {{ $sectionNumber }}</p>
                    <div class="grid grid-cols-8 gap-1 sm:grid-cols-10 lg:grid-cols-5">
                        @foreach ($items as $item)
                            @php
                                $status = $item['status'] ?? 'unanswered';
                                $classes = match ($status) {
                                    'current' => 'bg-brand-600 text-white border-brand-600 ring-2 ring-brand-400',
                                    'answered' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                    'flagged' => 'bg-amber-50 text-amber-900 border-amber-200',
                                    default => 'bg-white text-neutral-700 border-neutral-200',
                                };
                            @endphp
                            <button
                                type="button"
                                data-question-id="{{ $item['question_id'] ?? '' }}"
                                data-question-number="{{ $item['question_number'] ?? $item['number'] }}"
                                class="listening-palette-item rounded-md border px-1 py-1 text-xs font-medium {{ $classes }}"
                                data-status="{{ $status }}"
                            >
                                {{ $item['question_number'] ?? $item['number'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</aside>
