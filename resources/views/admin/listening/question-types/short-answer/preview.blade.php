@foreach ($preview['questions'] ?? [] as $q)
    <p class="text-sm"><strong>Q{{ $q['number'] }}.</strong> {{ $q['text'] ?? '' }} <span class="aa-muted">(max {{ $q['word_limit'] ?? '?' }} words)</span></p>
@endforeach
