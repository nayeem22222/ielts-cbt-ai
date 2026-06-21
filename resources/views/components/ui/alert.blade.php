@props(['tone'=>'blue','title'=>null])
@php $c=['blue'=>'border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-900/50 dark:bg-blue-950/40 dark:text-blue-100','green'=>'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-100','red'=>'border-red-200 bg-red-50 text-red-900 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-100']; @endphp
<div {{ $attributes->merge(['class'=>'rounded-2xl border p-4 text-sm '.($c[$tone]??$c['blue'])]) }}>
 @if($title)<p class="font-semibold">{{ $title }}</p>@endif
 <div class="mt-1">{{ $slot }}</div>
</div>
