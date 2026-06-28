@if (! empty($preview['image_path']))
    <div class="mb-3 rounded border border-dashed p-6 text-center text-sm aa-muted">Image: {{ $preview['image_path'] }}</div>
@endif
<ul class="text-sm">@foreach ($preview['options']['labels'] ?? [] as $l)<li>{{ $l['key'] }}. {{ $l['text'] }}</li>@endforeach</ul>
