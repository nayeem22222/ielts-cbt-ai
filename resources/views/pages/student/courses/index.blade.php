<x-layouts.dashboard heading="My Courses" eyebrow="Learning">
    <x-ui.card title="Enrolled Courses">
        <div class="space-y-4">
            @forelse ($enrollments as $enrollment)
                <div class="flex items-center gap-4 rounded-2xl border border-neutral-100 p-4 dark:border-neutral-800">
                    <div class="h-14 w-14 rounded-2xl bg-brand-50 dark:bg-brand-500/10"></div>
                    <div class="flex-1">
                        <h3 class="font-semibold">{{ $enrollment->course->title }}</h3>
                        <p class="text-sm aa-muted">{{ $enrollment->course->category?->name }}</p>
                        <x-ui.progress :value="$enrollment->progress_percent" class="mt-2"/>
                    </div>
                    <x-ui.button href="{{ route('student.courses.show', $enrollment->course) }}" variant="outline" size="sm">
                        Open
                    </x-ui.button>
                </div>
            @empty
                <p class="aa-muted">Enroll in a package to unlock courses.</p>
            @endforelse
        </div>
    </x-ui.card>
</x-layouts.dashboard>
