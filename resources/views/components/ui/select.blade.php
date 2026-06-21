@props(['label'=>null])
<label class="block">@if($label)<span class="mb-2 block text-sm font-medium">{{ $label }}</span>@endif<select {{ $attributes->merge(['class'=>'w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm dark:border-neutral-800 dark:bg-neutral-900']) }}>{{ $slot }}</select></label>
