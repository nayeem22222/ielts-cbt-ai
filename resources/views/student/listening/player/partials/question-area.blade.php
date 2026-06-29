@php
    $groupsBySection = collect($payload['groups'] ?? [])->groupBy('section_number');
@endphp

<div id="listening-question-area" class="listening-question-area">
    @foreach ($payload['sections'] ?? [] as $section)
        @php
            $sectionGroups = $groupsBySection->get($section['number'], collect());
            $isActiveSection = (int) ($payload['current_section_number'] ?? 1) === (int) $section['number'];
            $start = (int) ($section['start_question_number'] ?? 0);
            $end = (int) ($section['end_question_number'] ?? 0);
        @endphp
        <section
            class="listening-section {{ $isActiveSection ? '' : 'hidden' }}"
            data-section="{{ $section['number'] }}"
            data-start="{{ $start }}"
            data-end="{{ $end }}"
        >
            <div class="listening-part-intro">
                <p class="listening-part-intro__label">Part {{ $section['number'] }}</p>
                @if ($start > 0 && $end > 0)
                    <p class="listening-part-intro__range">Questions {{ $start }}@if ($end !== $start)–{{ $end }}@endif</p>
                @endif
                @if (! empty($section['instruction']))
                    <p class="listening-part-intro__instruction">{{ $section['instruction'] }}</p>
                @endif
            </div>

            <div class="listening-section-groups">
                @forelse ($sectionGroups as $group)
                    @include('student.listening.player.partials.question-group', ['group' => $group])
                @empty
                    <p class="listening-empty-note">No question groups in this part.</p>
                @endforelse
            </div>
        </section>
    @endforeach
</div>
