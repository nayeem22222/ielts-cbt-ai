<article
    class="listening-group-shell listening-question-group"
    data-group-id="{{ $group['id'] }}"
    data-section="{{ $group['section_number'] }}"
    data-start="{{ $group['start_question_number'] }}"
    data-end="{{ $group['end_question_number'] }}"
    data-question-type="{{ $group['question_type'] }}"
>
    <div class="listening-group-shell__inner">
        <header class="listening-group-header">
            <p class="listening-group-range">
                Questions {{ $group['start_question_number'] }}@if ((int) $group['end_question_number'] !== (int) $group['start_question_number'])–{{ $group['end_question_number'] }}@endif
            </p>
            @if (! empty($group['type_label']))
                <p class="listening-group-type">{{ $group['type_label'] }}</p>
            @endif
            @if (! empty($group['instruction']))
                <p class="listening-group-instruction">{{ $group['instruction'] }}</p>
            @endif
        </header>

        <div class="listening-group-body">
            {!! $group['rendered_html'] ?? '' !!}
        </div>
    </div>
</article>
