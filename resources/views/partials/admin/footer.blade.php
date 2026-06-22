<footer class="border-t border-neutral-200 bg-white px-4 py-4 text-sm dark:border-neutral-800 dark:bg-neutral-950 sm:px-6 lg:px-8">
  <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <p class="aa-muted">
      &copy; {{ now()->year }} Arif Academy IELTS CBT. All rights reserved.
    </p>
    <div class="flex flex-wrap items-center gap-4">
      <a href="{{ route('admin.dashboard') }}" class="font-medium text-neutral-600 transition hover:text-brand-600 dark:text-neutral-400 dark:hover:text-blue-300">Dashboard</a>
      <a href="{{ route('account.devices.index') }}" class="font-medium text-neutral-600 transition hover:text-brand-600 dark:text-neutral-400 dark:hover:text-blue-300">Security</a>
      <a href="{{ route('ui.index') }}" class="font-medium text-neutral-600 transition hover:text-brand-600 dark:text-neutral-400 dark:hover:text-blue-300">UI Kit</a>
      <span class="aa-muted">v1.0</span>
    </div>
  </div>
</footer>
