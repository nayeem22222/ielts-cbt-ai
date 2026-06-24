<x-layouts.admin :title="$test->title.' Preview'" :heading="$test->title" eyebrow="Reading Test Preview" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Reading Tests', 'href' => route('admin.reading-tests.index')], ['label' => 'Preview']]">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-neutral-900 dark:text-white">Admin Preview</h2>
            <p class="text-sm aa-muted">Selected test ID {{ $test->id }} · {{ $test->slug }}</p>
        </div>
        <div class="flex gap-2">
            <x-ui.button href="{{ route('admin.reading-tests.builder', $test) }}" variant="outline">Builder</x-ui.button>
            <x-ui.button href="{{ route('admin.reading-tests.edit', $test) }}" variant="outline">Settings</x-ui.button>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.card title="Test Details" class="lg:col-span-1">
            <dl class="space-y-3 text-sm">
                <div><dt class="font-semibold">Title</dt><dd class="aa-muted">{{ $test->title }}</dd></div>
                <div><dt class="font-semibold">Exam Type</dt><dd class="aa-muted">{{ $test->exam_type?->label() }}</dd></div>
                <div><dt class="font-semibold">Duration</dt><dd class="aa-muted">{{ $test->duration_minutes }} minutes</dd></div>
                <div><dt class="font-semibold">Status</dt><dd class="aa-muted">{{ $test->status?->label() }}</dd></div>
                <div><dt class="font-semibold">Published At</dt><dd class="aa-muted">{{ $test->published_at?->format('Y-m-d H:i') ?? '—' }}</dd></div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Content Counts" class="lg:col-span-2">
            <dl class="grid gap-4 md:grid-cols-3">
                <div>
                    <dt class="text-xs uppercase aa-muted">Passages</dt>
                    <dd class="text-2xl font-bold">{{ $test->passages->count() }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase aa-muted">Question Groups</dt>
                    <dd class="text-2xl font-bold">{{ $test->passages->sum(fn ($passage) => $passage->groups->count()) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase aa-muted">Questions</dt>
                    <dd class="text-2xl font-bold">{{ $test->questions_count }}</dd>
                </div>
            </dl>
        </x-ui.card>
    </div>
</x-layouts.admin>
