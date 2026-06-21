@props(['title'=>null,'subtitle'=>null,'padding'=>'p-6'])
<section {{ $attributes->merge(['class'=>'aa-card '.$padding]) }}>
 @if($title || $subtitle)
 <div class="mb-5 flex items-start justify-between gap-4">
  <div><h3 class="text-lg font-semibold text-neutral-950 dark:text-white">{{ $title }}</h3>@if($subtitle)<p class="mt-1 text-sm aa-muted">{{ $subtitle }}</p>@endif</div>
  {{ $actions ?? '' }}
 </div>
 @endif
 {{ $slot }}
</section>
