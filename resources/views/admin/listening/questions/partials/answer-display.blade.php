@php
    $answers = $question->correct_answer;
    if (is_string($answers)) {
        $answers = json_decode($answers, true) ?: [];
    }
    $accepted = $question->accepted_answers;
    if (is_string($accepted)) {
        $accepted = json_decode($accepted, true) ?: [];
    }
    $lines = [];
    if (is_array($answers)) {
        foreach ($answers as $item) {
            if (! is_array($item)) {
                $lines[] = (string) $item;
                continue;
            }
            if (isset($item['item_key'], $item['value'])) {
                $lines[] = $item['item_key'].' → '.$item['value'];
            } elseif (isset($item['label'], $item['value'])) {
                $lines[] = 'Label '.$item['label'].': '.$item['value'];
            } elseif (isset($item['value'])) {
                $lines[] = (string) $item['value'];
            }
        }
    }
    $acceptedLines = [];
    if (is_array($accepted)) {
        foreach ($accepted as $item) {
            if (is_array($item) && isset($item['value'])) {
                $acceptedLines[] = (string) $item['value'];
            } elseif (is_string($item)) {
                $acceptedLines[] = $item;
            }
        }
    }
@endphp
@if ($lines !== [])
    <ul class="list-disc space-y-1 pl-5 text-sm">
        @foreach ($lines as $line)
            <li>{{ $line }}</li>
        @endforeach
    </ul>
@else
    <p class="text-sm aa-muted">No answer set yet.</p>
@endif
@if ($acceptedLines !== [])
    <p class="mt-3 text-xs font-medium aa-muted">Also accepted</p>
    <p class="text-sm">{{ implode(', ', $acceptedLines) }}</p>
@endif
<details class="mt-4">
    <summary class="cursor-pointer text-xs font-medium text-neutral-600 dark:text-neutral-400">View raw JSON</summary>
    <pre class="mt-2 overflow-x-auto rounded-xl bg-neutral-100 p-3 text-xs dark:bg-neutral-900">{{ json_encode($question->correct_answer, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
</details>
