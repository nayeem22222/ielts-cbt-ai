<x-layouts.dashboard heading="Learning Dashboard" eyebrow="Welcome back, {{ auth()->user()->name }}">
    @if (! $hasEnrollment)
        <x-ui.alert tone="blue" class="mb-6">
            Activate a package to unlock courses, downloads, assignments, and live classes.
            <a href="{{ route('student.packages.index') }}" class="ml-1 font-semibold underline">Browse packages</a>
        </x-ui.alert>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Overall Progress" :value="$stats['overallProgress'].'%'" change="{{ $stats['activeCourses'] }} active course(s)" icon="📈"/>
        <x-ui.stat-card label="Live Classes" :value="str_pad((string) $stats['liveClasses'], 2, '0', STR_PAD_LEFT)" change="Upcoming sessions" icon="🎥"/>
        <x-ui.stat-card label="Downloads" :value="(string) $stats['downloads']" change="Available resources" icon="⬇️"/>
        <x-ui.stat-card label="Certificates" :value="(string) $stats['certificates']" change="{{ $stats['assignments'] }} assignment(s)" icon="🏅"/>
    </div>

    <section id="continue-learning" class="mt-6">
        <x-ui.card title="Continue Learning" subtitle="Pick up where you left off">
            @if ($continueLearning)
                <div class="grid gap-6 lg:grid-cols-[1.2fr_.8fr] lg:items-center">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-3xl bg-brand-50 text-3xl dark:bg-brand-500/10">
                            @switch($continueLearning['lesson']->content_type->value)
                                @case('video') 🎬 @break
                                @case('live') 🎥 @break
                                @case('quiz') 📝 @break
                                @default 📖
                            @endswitch
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-brand-600 dark:text-brand-300">{{ $continueLearning['course']->title }}</p>
                            <h3 class="mt-1 text-xl font-bold text-neutral-900 dark:text-white">{{ $continueLearning['lesson']->title }}</h3>
                            <p class="mt-2 text-sm aa-muted">{{ $continueLearning['lesson']->content_type->label() }} • {{ gmdate('i:s', $continueLearning['lesson']->duration_seconds) }}</p>
                        </div>
                    </div>
                    <div>
                        <div class="mb-3 flex items-center justify-between text-sm">
                            <span class="aa-muted">Course progress</span>
                            <span class="font-semibold text-neutral-900 dark:text-white">{{ $continueLearning['progressPercent'] }}%</span>
                        </div>
                        <x-ui.progress :value="$continueLearning['progressPercent']"/>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <x-ui.button href="{{ $continueLearning['resumeUrl'] }}">Resume Lesson</x-ui.button>
                            <x-ui.button href="{{ route('student.courses.index') }}" variant="outline">All Courses</x-ui.button>
                        </div>
                    </div>
                </div>
            @else
                <x-ui.empty-state title="No learning activity yet">
                    Enroll in a package to start your first lesson.
                    <div class="mt-4">
                        <x-ui.button href="{{ route('student.packages.index') }}">View Packages</x-ui.button>
                    </div>
                </x-ui.empty-state>
            @endif
        </x-ui.card>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.35fr_.65fr]">
        <section id="course-progress">
            <x-ui.card title="Course Progress" subtitle="Track completion across enrolled courses">
                <div class="space-y-4">
                    @forelse ($courseProgress as $item)
                        <div class="rounded-2xl border border-neutral-100 p-4 dark:border-neutral-800">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                                <div class="flex min-w-0 flex-1 items-center gap-4">
                                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-neutral-100 text-xl dark:bg-neutral-800">📚</div>
                                    <div class="min-w-0">
                                        <h4 class="truncate font-semibold text-neutral-900 dark:text-white">{{ $item['course']->title }}</h4>
                                        <p class="text-sm aa-muted">{{ $item['category'] ?? 'Course' }}</p>
                                    </div>
                                </div>
                                <div class="w-full sm:max-w-xs">
                                    <div class="mb-2 flex items-center justify-between text-sm">
                                        <span class="aa-muted">Progress</span>
                                        <span class="font-semibold">{{ $item['progressPercent'] }}%</span>
                                    </div>
                                    <x-ui.progress :value="$item['progressPercent']"/>
                                </div>
                                <x-ui.button href="{{ $item['url'] }}" variant="outline" size="sm" class="shrink-0">Open</x-ui.button>
                            </div>
                        </div>
                    @empty
                        <x-ui.empty-state title="No enrolled courses">
                            Your course progress will appear here after package activation.
                        </x-ui.empty-state>
                    @endforelse
                </div>
            </x-ui.card>
        </section>

        <section id="live-classes">
            <x-ui.card title="Upcoming Live Classes" subtitle="Join scheduled sessions">
                <div class="space-y-3">
                    @forelse ($liveClasses as $class)
                        <div class="rounded-2xl bg-neutral-50 p-4 dark:bg-neutral-800/60">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-neutral-900 dark:text-white">{{ $class['lesson']->title }}</p>
                                    <p class="mt-1 truncate text-sm aa-muted">{{ $class['course']->title }}</p>
                                    <p class="mt-2 text-sm font-medium text-brand-600 dark:text-brand-300">
                                        {{ $class['scheduledAt']->format('D, M j • g:i A') }}
                                    </p>
                                </div>
                                <x-ui.badge tone="blue">Live</x-ui.badge>
                            </div>
                            <div class="mt-3">
                                <x-ui.button href="{{ $class['url'] }}" variant="outline" size="sm">View Class</x-ui.button>
                            </div>
                        </div>
                    @empty
                        <x-ui.empty-state title="No live classes scheduled">
                            Live sessions from your courses will show up here.
                        </x-ui.empty-state>
                    @endforelse
                </div>
            </x-ui.card>
        </section>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section id="downloads">
            <x-ui.card title="Downloads" subtitle="PDF notes, sheets, and study files">
                <div class="space-y-3">
                    @forelse ($downloads as $download)
                        <div class="flex flex-col gap-3 rounded-2xl border border-neutral-100 p-4 sm:flex-row sm:items-center dark:border-neutral-800">
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                <div class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-brand-50 text-lg dark:bg-brand-500/10">📄</div>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-neutral-900 dark:text-white">{{ $download->title }}</p>
                                    <p class="truncate text-sm aa-muted">{{ $download->course?->title ?? 'Course resource' }}</p>
                                </div>
                            </div>
                            <x-ui.badge tone="neutral">{{ strtoupper($download->file_type->value) }}</x-ui.badge>
                        </div>
                    @empty
                        <x-ui.empty-state title="No downloads available">
                            Downloadable resources from your courses will appear here.
                        </x-ui.empty-state>
                    @endforelse
                </div>
            </x-ui.card>
        </section>

        <section id="assignments">
            <x-ui.card title="Assignments" subtitle="Quizzes and tasks awaiting submission">
                <div class="space-y-3">
                    @forelse ($assignments as $assignment)
                        <div class="rounded-2xl border border-neutral-100 p-4 dark:border-neutral-800">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <p class="font-semibold text-neutral-900 dark:text-white">{{ $assignment['lesson']->title }}</p>
                                    <p class="mt-1 text-sm aa-muted">{{ $assignment['course']->title }}</p>
                                </div>
                                <x-ui.badge :tone="$assignment['progressPercent'] >= 100 ? 'green' : 'amber'">
                                    {{ $assignment['status'] }}
                                </x-ui.badge>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-3">
                                <div class="min-w-[120px] flex-1">
                                    <x-ui.progress :value="$assignment['progressPercent']"/>
                                </div>
                                <x-ui.button href="{{ $assignment['url'] }}" variant="outline" size="sm">Open</x-ui.button>
                            </div>
                        </div>
                    @empty
                        <x-ui.empty-state title="No assignments yet">
                            Quizzes and assignment lessons will appear here.
                        </x-ui.empty-state>
                    @endforelse
                </div>
            </x-ui.card>
        </section>
    </div>

    <section id="certificates" class="mt-6">
        <x-ui.card title="Certificates" subtitle="Completed courses and achievements">
            @if ($certificates->isNotEmpty())
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($certificates as $certificate)
                        <div class="rounded-3xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-5 dark:border-emerald-900/40 dark:from-emerald-950/40 dark:to-neutral-950">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Certificate</p>
                                    <h4 class="mt-2 text-lg font-bold text-neutral-900 dark:text-white">{{ $certificate['course']->title }}</h4>
                                    <p class="mt-2 text-sm aa-muted">Issued {{ $certificate['issuedAt']->format('M j, Y') }}</p>
                                </div>
                                <span class="text-3xl" aria-hidden="true">🏅</span>
                            </div>
                            <div class="mt-4">
                                <x-ui.button href="{{ $certificate['url'] }}" variant="outline" size="sm">View Course</x-ui.button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state title="No certificates earned yet">
                    Complete a course to unlock your first certificate.
                </x-ui.empty-state>
            @endif
        </x-ui.card>
    </section>

    @if ($accessibleModules !== [])
        <section class="mt-6">
            <x-ui.card title="Quick Practice" subtitle="Modules available on your active package">
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($accessibleModules as $module)
                        @php($examRoute = 'exam.'.$module)
                        @if (Route::has($examRoute))
                            <x-ui.button href="{{ route($examRoute) }}" variant="outline" class="justify-center">
                                {{ ucfirst($module) }} Test
                            </x-ui.button>
                        @endif
                    @endforeach
                </div>
            </x-ui.card>
        </section>
    @endif
</x-layouts.dashboard>
