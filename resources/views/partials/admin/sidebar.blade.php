@php
    $navSections = [
        [
            'key' => 'overview',
            'label' => 'Overview',
            'items' => [
                ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'dashboard', 'active' => request()->routeIs('admin.dashboard')],
            ],
        ],
        [
            'key' => 'access',
            'label' => 'Access Management',
            'items' => [
                ['label' => 'Users', 'href' => route('admin.users.index'), 'icon' => 'users', 'active' => request()->routeIs('admin.users.*')],
                ['label' => 'Roles', 'href' => route('admin.roles.index'), 'icon' => 'roles', 'active' => request()->routeIs('admin.roles.*')],
                ['label' => 'Permissions', 'href' => route('admin.permissions.index'), 'icon' => 'permissions', 'active' => request()->routeIs('admin.permissions.*')],
            ],
        ],
        [
            'key' => 'platform',
            'label' => 'Platform',
            'items' => [
                ['label' => 'Courses', 'href' => route('courses.index'), 'icon' => 'courses', 'active' => request()->routeIs('courses.*')],
                ['label' => 'Settings', 'href' => route('admin.settings.index'), 'icon' => 'settings', 'active' => request()->routeIs('admin.settings.*')],
                ['label' => 'UI Kit', 'href' => route('ui.index'), 'icon' => 'ui', 'active' => request()->routeIs('ui.*')],
            ],
        ],
    ];
@endphp

<div class="flex h-full flex-col">
  <a href="{{ route('admin.dashboard') }}" class="mb-6 flex items-center gap-3 overflow-hidden">
    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-brand-500 font-black text-white">A</span>
    <span class="min-w-0 transition-opacity" :class="collapsed ? 'opacity-0 lg:hidden' : 'opacity-100'">
      <strong class="block truncate">Arif Academy</strong>
      <small class="block truncate aa-muted">Admin Console</small>
    </span>
  </a>

  <nav class="flex-1 space-y-4 overflow-y-auto pr-1" aria-label="Admin navigation">
    @foreach ($navSections as $section)
      <div>
        <button
          type="button"
          class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500 transition hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-900"
          @click="toggleMenu('{{ $section['key'] }}')"
          :class="collapsed ? 'lg:justify-center lg:px-2' : ''"
        >
          <span :class="collapsed ? 'lg:hidden' : ''">{{ $section['label'] }}</span>
          <svg class="h-4 w-4 transition" :class="[isMenuOpen('{{ $section['key'] }}') ? 'rotate-180' : '', collapsed ? 'lg:hidden' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>

        <div x-show="isMenuOpen('{{ $section['key'] }}')" class="mt-1 space-y-1">
          @foreach ($section['items'] as $item)
            <x-ui.sidebar-link href="{{ $item['href'] }}" :active="$item['active']">
              <span class="shrink-0 text-base" aria-hidden="true">
                @switch($item['icon'])
                  @case('dashboard') 🏠 @break
                  @case('users') 👥 @break
                  @case('roles') 🛡️ @break
                  @case('permissions') 🔐 @break
                  @case('courses') 📚 @break
                  @case('settings') ⚙️ @break
                  @case('ui') 🧩 @break
                @endswitch
              </span>
              <span class="truncate" :class="collapsed ? 'lg:hidden' : ''">{{ $item['label'] }}</span>
            </x-ui.sidebar-link>
          @endforeach
        </div>
      </div>
    @endforeach
  </nav>

  <div class="mt-4 rounded-3xl bg-gradient-to-br from-brand-500 to-brand-600 p-4 text-white transition-opacity" :class="collapsed ? 'lg:hidden' : ''">
    <p class="font-semibold">Enterprise Admin</p>
    <p class="mt-1 text-sm text-blue-100">RBAC, audit logs, and device security are active.</p>
  </div>
</div>
