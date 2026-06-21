@props(['label'=>null,'error'=>null,'help'=>null])
<label class="block">
 @if($label)<span class="mb-2 block text-sm font-medium text-neutral-800 dark:text-neutral-200">{{ $label }}</span>@endif
 <input {{ $attributes->merge(['class'=>'w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 placeholder:text-neutral-400 transition focus:border-brand-500 dark:border-neutral-800 dark:bg-neutral-900 dark:text-white']) }}>
 @if($help)<span class="mt-1.5 block text-xs aa-muted">{{ $help }}</span>@endif
 @if($error)<span class="mt-1.5 block text-xs text-danger-500">{{ $error }}</span>@endif
</label>
