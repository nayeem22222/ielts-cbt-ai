@props(['items'=>[]])
<nav aria-label="Breadcrumb" class="flex items-center gap-2 text-sm aa-muted">
 @foreach($items as $item)<span>{{ $item }}</span>@if(!$loop->last)<span>/</span>@endif @endforeach
</nav>
