<x-ui.card title="Section Usage">
    @if ($usage['sections']->isEmpty() && $usage['question_groups']->isEmpty() && $usage['transcripts']->isEmpty())
        <p class="text-sm aa-muted">This audio is not attached to any section, group, or transcript.</p>
    @else
        <ul class="space-y-2 text-sm">
            @foreach ($usage['sections'] as $section)
                <li>Section {{ $section->section_number }} · Test #{{ $section->listening_test_id }}</li>
            @endforeach
            @foreach ($usage['transcripts'] as $transcript)
                <li>Transcript: {{ $transcript->title ?: '#'.$transcript->id }}</li>
            @endforeach
        </ul>
    @endif
</x-ui.card>
