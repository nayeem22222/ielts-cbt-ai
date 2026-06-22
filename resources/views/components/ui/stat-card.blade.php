@props(['label','value','change'=>null,'tone'=>'blue','icon'=>'↗'])
<x-ui.card padding="p-5">
 <div class="flex items-center justify-between gap-4">
  <div><p class="text-sm aa-muted">{{ $label }}</p><p class="mt-2 text-3xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ $value }}</p>@if($change)<p class="mt-2 text-xs text-emerald-600 dark:text-emerald-400">{{ $change }}</p>@endif</div>
  <div class="grid h-12 w-12 place-items-center rounded-2xl bg-brand-50 text-xl text-brand-500 dark:bg-brand-500/10">{{ $icon }}</div>
 </div>
</x-ui.card>
