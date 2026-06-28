@if (! empty($preview['settings']['display_instruction']))
    <p class="text-sm font-medium">{{ $preview['settings']['display_instruction'] }}</p>
@endif
<ul class="mt-2 space-y-1 text-sm">
    @foreach ($preview['options'] ?? [] as $option)
        <li>{{ $option['key'] ?? '' }}. {{ $option['text'] ?? '' }}</li>
    @endforeach
</ul>
