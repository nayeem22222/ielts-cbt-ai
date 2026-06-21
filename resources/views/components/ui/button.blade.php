@props(['variant'=>'primary','size'=>'md','href'=>null,'loading'=>false])
@php
$base='inline-flex items-center justify-center gap-2 rounded-2xl font-semibold transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-60';
$sizes=['sm'=>'px-3 py-2 text-sm','md'=>'px-4 py-2.5 text-sm','lg'=>'px-5 py-3 text-base'];
$variants=[
 'primary'=>'bg-brand-500 text-white shadow-lg shadow-blue-600/20 hover:-translate-y-0.5 hover:bg-brand-600',
 'secondary'=>'bg-neutral-900 text-white hover:bg-neutral-800 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200',
 'outline'=>'border border-neutral-200 bg-white text-neutral-800 hover:border-brand-200 hover:bg-brand-50 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-100 dark:hover:bg-neutral-800',
 'ghost'=>'text-neutral-700 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800',
 'danger'=>'bg-danger-500 text-white hover:bg-red-600',
];
$class=$base.' '.($sizes[$size]??$sizes['md']).' '.($variants[$variant]??$variants['primary']);
@endphp
@if($href)
<a href="{{ $href }}" {{ $attributes->merge(['class'=>$class]) }}>{{ $slot }}</a>
@else
<button {{ $attributes->merge(['class'=>$class]) }} @disabled($loading)>
 @if($loading)<span class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"></span>@endif
 {{ $slot }}
</button>
@endif
