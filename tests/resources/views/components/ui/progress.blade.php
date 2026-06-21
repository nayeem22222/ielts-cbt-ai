@props(['value'=>50])
<div class="h-2 overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800" role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100">
 <div class="h-full rounded-full bg-brand-500" style="width: {{ $value }}%"></div>
</div>
