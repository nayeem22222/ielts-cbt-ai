@props(['side'=>'right'])
<div x-data="{open:false}"><span @click="open=true">{{ $trigger ?? '' }}</span><div x-show="open" x-cloak class="fixed inset-0 z-50 bg-neutral-950/40"><aside @click.outside="open=false" class="absolute right-0 top-0 h-full w-full max-w-md bg-white p-6 shadow-2xl dark:bg-neutral-950">{{ $slot }}</aside></div></div>
