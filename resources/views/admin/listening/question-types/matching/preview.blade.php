<div class="grid gap-4 sm:grid-cols-2 text-sm">
    <div><p class="font-medium">Items</p><ul class="mt-1">@foreach ($preview['options']['items'] ?? [] as $item)<li>{{ $item['key'] }}. {{ $item['text'] }}</li>@endforeach</ul></div>
    <div><p class="font-medium">Choices</p><ul class="mt-1">@foreach ($preview['options']['choices'] ?? [] as $c)<li>{{ $c['key'] }}. {{ $c['text'] }}</li>@endforeach</ul></div>
</div>
