<header class="sticky top-0 z-30 border-b border-neutral-200 bg-white/90 backdrop-blur-xl dark:border-neutral-800 dark:bg-neutral-950/90">
  <div class="flex h-16 items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
    <div class="flex min-w-0 items-center gap-3">
      <button
        type="button"
        class="rounded-xl p-2 text-neutral-600 transition hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800 lg:hidden"
        @click="toggleMobile()"
        aria-label="Open navigation menu"
      >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>

      <button
        type="button"
        class="hidden rounded-xl p-2 text-neutral-600 transition hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800 lg:inline-flex"
        @click="toggleCollapsed()"
        aria-label="Toggle sidebar"
      >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
        </svg>
      </button>

      <div class="min-w-0">
        <p class="truncate text-xs font-semibold uppercase tracking-wide text-brand-500">{{ $eyebrow ?? 'Operations Control Center' }}</p>
        <h1 class="truncate text-base font-bold sm:text-lg">{{ $heading ?? 'Admin Dashboard' }}</h1>
      </div>
    </div>

    <div class="flex shrink-0 items-center gap-1 sm:gap-2">
      <button
        type="button"
        class="rounded-xl p-2 text-neutral-600 transition hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800"
        @click="toggleDark()"
        :aria-label="dark ? 'Switch to light mode' : 'Switch to dark mode'"
      >
        <svg x-show="!dark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
        </svg>
        <svg x-show="dark" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
      </button>

      <a href="{{ route('account.devices.index') }}" class="hidden rounded-xl p-2 text-neutral-600 transition hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800 sm:inline-flex" title="Devices">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
      </a>

      <x-ui.avatar :name="auth()->user()?->name ?? 'Admin'" class="hidden sm:grid"/>

      <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
        @csrf
        <button type="submit" class="rounded-xl px-3 py-2 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-800">
          Logout
        </button>
      </form>
    </div>
  </div>
</header>
