<x-layouts.app :title="$title ?? 'Dashboard'">
<div x-data="{sidebar:false}" class="min-h-screen bg-neutral-50 dark:bg-neutral-950">
 <aside class="fixed inset-y-0 left-0 z-40 hidden w-72 border-r border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-950 lg:block">
  @include('partials.sidebar',['role'=>$role ?? 'student'])
 </aside>
 <div class="lg:pl-72">
  <header class="sticky top-0 z-30 border-b border-neutral-200 bg-white/80 backdrop-blur-xl dark:border-neutral-800 dark:bg-neutral-950/80">
   <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
    <div class="flex min-w-0 items-center gap-3">
     <button type="button" @click="sidebar=true" class="rounded-xl p-2 text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-800 lg:hidden" aria-label="Open navigation menu">☰</button>
     <div class="min-w-0">
      <p class="truncate text-sm aa-muted">{{ $eyebrow ?? 'IELTS CBT Platform' }}</p>
      <h1 class="truncate text-base font-bold text-neutral-900 dark:text-white sm:text-lg">{{ $heading ?? 'Dashboard' }}</h1>
     </div>
    </div>
    <div class="flex shrink-0 items-center gap-2">
     <button type="button" @click="dark=!dark" class="rounded-xl p-2 text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-800" aria-label="Toggle dark mode">◐</button>
     <x-ui.avatar :name="auth()->user()?->name ?? 'Guest'"/>
     <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="rounded-xl px-3 py-2 text-sm font-semibold text-neutral-800 hover:bg-neutral-100 dark:text-neutral-100 dark:hover:bg-neutral-800">Logout</button></form>
    </div>
   </div>
  </header>
  <main class="px-4 py-6 sm:px-6 lg:px-8">@if(session('status'))<x-ui.alert tone="green" class="mb-4">{{ session('status') }}</x-ui.alert>@endif{{ $slot }}</main>
 </div>
 <div x-show="sidebar" x-cloak class="fixed inset-0 z-50 bg-neutral-950/40 lg:hidden"><aside @click.outside="sidebar=false" class="h-full w-72 bg-white p-4 dark:bg-neutral-950">@include('partials.sidebar',['role'=>$role ?? 'student'])</aside></div>
</div>
</x-layouts.app>
