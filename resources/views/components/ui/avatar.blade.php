@props(['name'=>'Arif Academy','src'=>null])
@if($src)<img src="{{ $src }}" alt="{{ $name }}" {{ $attributes->merge(['class'=>'h-10 w-10 rounded-full object-cover']) }}>@else
<div {{ $attributes->merge(['class'=>'grid h-10 w-10 place-items-center rounded-full bg-brand-500 text-sm font-bold text-white']) }}>{{ collect(explode(' ',$name))->map(fn($p)=>substr($p,0,1))->take(2)->join('') }}</div>
@endif
