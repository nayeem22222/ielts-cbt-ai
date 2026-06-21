@props(['total'=>40,'active'=>1])
<div class="grid grid-cols-5 gap-2">@for($i=1;$i<=$total;$i++)<button class="h-9 rounded-xl text-sm font-semibold {{ $i==$active?'bg-brand-500 text-white':($i%3==0?'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10':'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300') }}">{{ $i }}</button>@endfor</div>
