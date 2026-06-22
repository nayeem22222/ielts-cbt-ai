@props([
    'heading' => 'IELTS Mock Test',
    'title' => 'IELTS CBT Exam',
    'time' => '59:58',
])

<x-layouts.app :title="$title">
<div class="min-h-screen bg-neutral-50 dark:bg-neutral-950">
    <header class="sticky top-0 z-40 border-b border-neutral-200 bg-white/90 backdrop-blur-xl dark:border-neutral-800 dark:bg-neutral-950/90">
        <div class="flex h-16 items-center justify-between gap-3 px-4 sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-brand-500 font-black text-white">CBT</span>
                <div class="min-w-0">
                    <h1 class="truncate font-bold text-neutral-900 dark:text-white">{{ $heading }}</h1>
                    <p class="text-xs aa-muted">Autosaved • Accessible • Responsive</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                {{ $actions ?? '' }}
                <button type="button" @click="dark=!dark" class="rounded-xl border border-neutral-200 px-3 py-2 text-sm font-semibold text-neutral-700 hover:bg-neutral-100 dark:border-neutral-800 dark:text-neutral-200 dark:hover:bg-neutral-900" aria-label="Toggle dark mode">
                    <span x-show="!dark">Dark</span>
                    <span x-show="dark" x-cloak>Light</span>
                </button>
                <x-ui.timer :time="$time" />
            </div>
        </div>
    </header>
    <main>{{ $slot }}</main>
</div>
</x-layouts.app>
