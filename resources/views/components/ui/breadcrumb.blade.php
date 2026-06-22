@props(['items' => []])

<nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2 text-sm']) }}>
    @foreach ($items as $item)
        @php
            $label = is_array($item) ? ($item['label'] ?? '') : $item;
            $href = is_array($item) ? ($item['href'] ?? null) : null;
            $isLast = $loop->last;
        @endphp

        @if (! $loop->first)
            <span class="aa-muted" aria-hidden="true">/</span>
        @endif

        @if ($href && ! $isLast)
            <a href="{{ $href }}" class="font-medium text-neutral-600 transition hover:text-brand-600 dark:text-neutral-400 dark:hover:text-blue-300">
                {{ $label }}
            </a>
        @else
            <span @class([
                'font-medium',
                'text-neutral-900 dark:text-white' => $isLast,
                'aa-muted' => ! $isLast,
            ]) @if($isLast) aria-current="page" @endif>
                {{ $label }}
            </span>
        @endif
    @endforeach
</nav>
