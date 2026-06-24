@props([
    'passage',
    'title' => null,
    'subtitle' => null,
    'instruction' => null,
    'content' => null,
    'autoLabels' => null,
])

@php
    $displayTitle = $title ?? $passage?->title;
    $displaySubtitle = $subtitle ?? $passage?->subtitle;
    $displayInstruction = $instruction ?? $passage?->instruction;
    $useAutoLabels = $autoLabels ?? $passage?->auto_paragraph_labels ?? false;
    $html = $content ?? ($passage ? ($useAutoLabels ? $passage->renderedContentHtml() : ($passage->content_html ?? '')) : '');
@endphp

<article class="reading-passage-preview mx-auto max-w-3xl">
    @if ($displayTitle)
        <h2 class="mb-2 text-center text-xl font-bold text-neutral-900 dark:text-white">{{ $displayTitle }}</h2>
    @endif

    @if ($displaySubtitle)
        <p class="mb-4 text-center text-sm font-medium text-neutral-600 dark:text-neutral-300">{{ $displaySubtitle }}</p>
    @endif

    @if ($displayInstruction)
        <p class="mb-6 text-sm italic leading-7 text-neutral-600 dark:text-neutral-300">{{ $displayInstruction }}</p>
    @endif

    <div class="reading-passage-preview-body font-serif text-[15px] leading-8 text-neutral-900 dark:text-neutral-100 [&_h1]:mb-4 [&_h1]:text-2xl [&_h1]:font-bold [&_h2]:mb-3 [&_h2]:text-xl [&_h2]:font-bold [&_h3]:mb-2 [&_h3]:text-lg [&_h3]:font-semibold [&_ol]:my-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_p]:mb-4 [&_ul]:my-4 [&_ul]:list-disc [&_ul]:pl-6">
        {!! $html !!}
    </div>
</article>
