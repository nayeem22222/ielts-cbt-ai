<div class="flex h-full flex-col">
    <a href="{{ route('student.dashboard') }}" class="mb-6 flex items-center gap-3">
        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-brand-500 font-black text-white">A</span>
        <span>
            <strong class="text-neutral-900 dark:text-white">Arif Academy</strong>
            <small class="block aa-muted">Student LMS</small>
        </span>
    </a>

    <nav class="space-y-1" aria-label="Student navigation">
        @php
            $items = [
                ['Overview', route('student.dashboard'), request()->routeIs('student.dashboard')],
                ['My Courses', route('student.courses.index'), request()->routeIs('student.courses.*')],
                ['Packages', route('student.packages.index'), request()->routeIs('student.packages.*')],
                ['Continue Learning', route('student.dashboard').'#continue-learning', false],
                ['Live Classes', route('student.dashboard').'#live-classes', false],
                ['Downloads', route('student.dashboard').'#downloads', false],
                ['Assignments', route('student.dashboard').'#assignments', false],
                ['Certificates', route('student.dashboard').'#certificates', false],
            ];
            $icons = ['🏠', '📚', '📦', '▶️', '🎥', '⬇️', '📝', '🏅'];
        @endphp

        @foreach ($items as $index => $item)
            <x-ui.sidebar-link href="{{ $item[1] }}" :active="$item[2]">
                <span aria-hidden="true">{{ $icons[$index] ?? '📌' }}</span>
                {{ $item[0] }}
            </x-ui.sidebar-link>
        @endforeach
    </nav>

    <div class="mt-auto rounded-3xl bg-gradient-to-br from-brand-500 to-brand-600 p-4 text-white">
        <p class="font-semibold">Upgrade learning</p>
        <p class="mt-1 text-sm text-blue-100">AI evaluation, analytics, and smart practice.</p>
        <x-ui.button href="{{ route('student.packages.index') }}" variant="secondary" size="sm" class="mt-4 !bg-white !text-brand-600 hover:!bg-blue-50">
            View Packages
        </x-ui.button>
    </div>
</div>
