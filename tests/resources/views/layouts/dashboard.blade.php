<x-layouts.app :title="$title ?? 'Dashboard'">
<div x-data="{sidebar:false}" class="min-h-screen bg-neutral-50 dark:bg-neutral-950">
 <aside class="fixed inset-y-0 left-0 z-40 hidden w-72 border-r border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-950 lg:block">
  @include('partials.sidebar',['role'=>$role ?? 'student'])
 </aside>
 <div class="lg:pl-72">
  <header class="sticky top-0 z-30 border-b border-neutral-200 bg-white/80 backdrop-blur-xl dark:border-neutral-800 dark:bg-neutral-950/80"><div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8"><div class="flex items-center gap-3"><button @click="sidebar=true" class="rounded-xl p-2 hover:bg-neutral-100 dark:hover:bg-neutral-800 lg:hidden">☰</button><div><p class="text-sm aa-muted">{{ $eyebrow ?? 'IELTS CBT Platform' }}</p><h1 class="text-base font-bold sm:text-lg">{{ $heading ?? 'Dashboard' }}</h1></div></div><div class="flex items-center gap-2"><button @click="dark=!dark" class="rounded-xl p-2 hover:bg-neutral-100 dark:hover:bg-neutral-800">◐</button><button class="rounded-xl p-2 hover:bg-neutral-100 dark:hover:bg-neutral-800">🔔</button><x-ui.avatar name="Arif Academy"/></div></div></header>
  <main class="px-4 py-6 sm:px-6 lg:px-8">{{ $slot }}</main>
 </div>
 <div x-show="sidebar" x-cloak class="fixed inset-0 z-50 bg-neutral-950/40 lg:hidden"><aside @click.outside="sidebar=false" class="h-full w-72 bg-white p-4 dark:bg-neutral-950">@include('partials.sidebar',['role'=>$role ?? 'student'])</aside></div>
</div>
</x-layouts.app>
