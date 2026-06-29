@php
    $paletteBySection = collect($payload['palette'] ?? [])->groupBy('section_number')->sortKeys();
    $sections = collect($payload['sections'] ?? []);
    $partNumbers = $sections->isNotEmpty()
        ? $sections->pluck('number')->sort()->values()
        : collect([1, 2, 3, 4]);
@endphp

<footer class="listening-part-footer" id="listening-part-footer">
    <div class="listening-part-tabs">
        @foreach ($partNumbers as $partNumber)
            @php
                $items = $paletteBySection->get($partNumber, collect());
                $section = $sections->firstWhere('number', $partNumber);
                $start = (int) ($section['start_question_number'] ?? 0);
                $end = (int) ($section['end_question_number'] ?? 0);
                $total = $items->count() ?: max(0, $end - $start + 1);
                $isActive = (int) ($payload['current_section_number'] ?? 1) === (int) $partNumber;
            @endphp
            <div
                class="listening-part-tab {{ $isActive ? 'is-expanded' : '' }}"
                data-section="{{ $partNumber }}"
                id="listening-part-box-{{ $partNumber }}"
                role="button"
                tabindex="0"
            >
                <span class="listening-part-tab-head">
                    <span class="listening-part-tab-head__row">
                        <span class="listening-part-tab-head__part">Part {{ $partNumber }}</span>
                        @if ($start > 0 && $end > 0)
                            <span class="listening-part-tab-head__range">Questions {{ $start }}–{{ $end }}</span>
                        @endif
                    </span>
                    <span class="listening-part-tab-head__status">
                        <span class="listening-part-answered-count" data-part="{{ $partNumber }}">0</span> of {{ $total }} answered
                    </span>
                </span>
                <div class="listening-part-q-grid" data-part-grid="{{ $partNumber }}" @if (! $isActive) hidden @endif>
                    @foreach ($items as $item)
                        @php $status = $item['status'] ?? 'unanswered'; @endphp
                        <span
                            role="button"
                            tabindex="0"
                            data-question-id="{{ $item['question_id'] ?? '' }}"
                            data-question-number="{{ $item['question_number'] ?? $item['number'] }}"
                            class="listening-palette-item listening-part-q is-{{ $status }}"
                            data-status="{{ $status }}"
                            title="Question {{ $item['question_number'] ?? $item['number'] }}"
                        >{{ $item['question_number'] ?? $item['number'] }}</span>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</footer>
