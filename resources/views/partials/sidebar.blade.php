<div class="flex h-full flex-col"><a href="/ui" class="mb-6 flex items-center gap-3"><span class="grid h-11 w-11 place-items-center rounded-2xl bg-brand-500 font-black text-white">A</span><span><strong>Arif Academy</strong><small class="block aa-muted">Premium IELTS SaaS</small></span></a><nav class="space-y-1">
@php
$sets = [
    'student' => [
        ['Overview', route('student.dashboard')],
        ['Courses', route('courses.index')],
        ['Mock Tests', '#'],
        ['Results', '#'],
        ['Classes', '#'],
        ['Downloads', '#'],
        ['Notifications', '#'],
    ],
    'teacher' => [
        ['Overview', route('teacher.dashboard')],
        ['Students', '#'],
        ['Courses', route('courses.index')],
        ['Results', '#'],
        ['Assignments', '#'],
        ['Live Classes', '#'],
        ['Analytics', '#'],
    ],
    'admin' => [
        ['Overview', route('admin.dashboard')],
        ['Users', route('admin.users.index')],
        ['Roles', '#'],
        ['Question Bank', '#'],
        ['Tests', '#'],
        ['Packages', '#'],
        ['Orders', '#'],
        ['Payments', '#'],
        ['Reports', '#'],
        ['AI Configuration', '#'],
        ['Settings', '#'],
    ],
];
@endphp
@foreach($sets[$role] ?? $sets['student'] as $i=>$item)
<x-ui.sidebar-link href="{{ $item[1] }}" :active="request()->fullUrlIs($item[1].'*') || request()->url() === $item[1]"><span>{{ ['🏠','📚','📝','📊','🎥','⬇️','🔔','👥','⚙️'][$i%9] }}</span>{{ $item[0] }}</x-ui.sidebar-link>
@endforeach
</nav><div class="mt-auto rounded-3xl bg-gradient-to-br from-brand-500 to-brand-600 p-4 text-white"><p class="font-semibold">Upgrade learning</p><p class="mt-1 text-sm text-blue-100">AI evaluation, analytics, and smart practice.</p></div></div>
