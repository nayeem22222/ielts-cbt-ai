@props([
    'title' => 'Admin',
    'heading' => 'Admin Dashboard',
    'eyebrow' => 'Operations Control Center',
    'breadcrumbs' => [],
])

<x-layouts.app :title="$title">
<div
  x-data="adminLayoutState()"
  x-init="init()"
  class="min-h-screen bg-neutral-50 dark:bg-neutral-950"
  data-admin-layout
  data-storage-dark="aa-admin-dark"
  data-storage-collapsed="aa-admin-sidebar-collapsed"
  data-storage-menu="aa-admin-menu-open"
>
  {{-- Desktop sidebar --}}
  <aside
    class="fixed inset-y-0 left-0 z-40 hidden border-r border-neutral-200 bg-white p-4 transition-all duration-300 dark:border-neutral-800 dark:bg-neutral-950 lg:block"
    :class="collapsed ? 'w-20' : 'w-72'"
    aria-label="Admin sidebar"
    data-admin-sidebar
  >
    @include('partials.admin.sidebar')
  </aside>

  <div class="flex min-h-screen min-w-0 flex-col overflow-x-hidden transition-all duration-300" :class="collapsed ? 'lg:pl-20' : 'lg:pl-72'">
    @include('partials.admin.topbar', ['heading' => $heading, 'eyebrow' => $eyebrow])

    <main class="min-w-0 flex-1 px-4 py-6 sm:px-6 lg:px-8">
      @if (count($breadcrumbs) > 0)
        <x-ui.breadcrumb :items="$breadcrumbs" class="mb-4" data-admin-breadcrumb />
      @endif

      @if (session('status'))
        <x-ui.alert tone="green" class="mb-4">{{ session('status') }}</x-ui.alert>
      @endif

      {{ $slot }}
    </main>

    @include('partials.admin.footer')
  </div>

  {{-- Mobile drawer --}}
  <div
    x-show="mobileOpen"
    x-cloak
    class="fixed inset-0 z-50 lg:hidden"
    data-admin-mobile-menu
  >
    <div class="absolute inset-0 bg-neutral-950/50" @click="closeMobile()" aria-hidden="true"></div>
    <aside
      class="relative h-full w-72 max-w-[85vw] border-r border-neutral-200 bg-white p-4 shadow-xl dark:border-neutral-800 dark:bg-neutral-950"
      @click.outside="closeMobile()"
      aria-label="Mobile admin navigation"
    >
      @include('partials.admin.sidebar')
    </aside>
  </div>
</div>
</x-layouts.app>
