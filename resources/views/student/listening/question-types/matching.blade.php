@php
    $options = $group['options'] ?? [];
    $items = $options['items'] ?? [];
    $choices = $options['choices'] ?? [];
    $saved = collect($question['student_answer'] ?? [])->keyBy(fn ($a) => $a['item_key'] ?? $a['label'] ?? '');
@endphp
<div class="overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead><tr><th class="text-left pr-4">Item</th><th class="text-left">Answer</th></tr></thead>
        <tbody>
            @foreach ($items as $item)
                @php $key = $item['key'] ?? ''; $savedValue = $saved->get($key)['value'] ?? ''; @endphp
                <tr>
                    <td class="py-2 pr-4 align-top">{{ $item['text'] ?? $key }}</td>
                    <td class="py-2">
                        <select class="listening-answer-input w-full rounded border px-2 py-1" data-question-id="{{ $question['id'] }}" data-item-key="{{ $key }}">
                            <option value="">—</option>
                            @foreach ($choices as $choice)
                                <option value="{{ $choice['key'] ?? '' }}" @selected($savedValue === ($choice['key'] ?? ''))>{{ $choice['key'] ?? '' }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
