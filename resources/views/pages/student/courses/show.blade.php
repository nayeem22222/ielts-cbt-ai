<x-layouts.dashboard :heading="$course->title" eyebrow="{{ $course->category?->name ?? 'Course' }}">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="aa-muted">{{ $course->description }}</p>
        </div>
        <x-ui.badge tone="blue">{{ $progressPercent }}% complete</x-ui.badge>
    </div>

    <div class="space-y-6">
        @foreach ($course->sections as $section)
            <x-ui.card :title="$section->title">
                <div class="space-y-3">
                    @foreach ($section->lessons as $lesson)
                        <div class="flex items-center justify-between rounded-2xl bg-neutral-50 p-4 dark:bg-neutral-800/60">
                            <div>
                                <p class="font-semibold">{{ $lesson->title }}</p>
                                <p class="text-sm aa-muted">{{ $lesson->content_type->label() }} • {{ gmdate('i:s', $lesson->duration_seconds) }}</p>
                            </div>
                            <x-ui.badge>{{ $lesson->status->label() }}</x-ui.badge>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        @endforeach
    </div>
</x-layouts.dashboard>
