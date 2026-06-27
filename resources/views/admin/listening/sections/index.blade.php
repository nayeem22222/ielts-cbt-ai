<x-layouts.admin :title="$listeningTest->title.' — Sections'" :heading="$listeningTest->title" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Listening Tests', 'href' => route($routePrefix.'.index')], ['label' => $listeningTest->title, 'href' => route($routePrefix.'.show', $listeningTest)], ['label' => 'Sections']]">
    @if (session('error'))
        <x-ui.alert tone="red" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    <div class="mb-6 flex flex-wrap justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-neutral-900 dark:text-white">Listening Sections</h2>
            <p class="text-sm aa-muted">{{ $listeningTest->test_code }} · @include('admin.listening.tests.partials.status-badge', ['status' => $listeningTest->status])</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-ui.button href="{{ route($routePrefix.'.show', $listeningTest) }}" variant="outline">Back to Test</x-ui.button>
            @can('createDefault', [App\Models\Listening\ListeningSection::class, $listeningTest])
                <form method="POST" action="{{ route($sectionsRoutePrefix.'.default', $listeningTest) }}">@csrf<x-ui.button type="submit" variant="outline">Auto Create 4 Sections</x-ui.button></form>
            @endcan
            @can('create', [App\Models\Listening\ListeningSection::class, $listeningTest])
                <x-ui.button href="{{ route($sectionsRoutePrefix.'.create', $listeningTest) }}">Add Section</x-ui.button>
            @endcan
        </div>
    </div>

    <x-ui.card title="Section Summary" class="mb-6">
        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 text-sm">
            <div><dt class="aa-muted">Sections</dt><dd class="text-lg font-semibold">{{ $summary['sections_count'] }}/{{ $summary['expected_sections'] }}</dd></div>
            <div><dt class="aa-muted">With Audio</dt><dd class="text-lg font-semibold">{{ $summary['sections_with_audio'] }}/{{ $summary['expected_sections'] }}</dd></div>
            <div><dt class="aa-muted">With Transcript</dt><dd class="text-lg font-semibold">{{ $summary['sections_with_transcript'] }}/{{ $summary['expected_sections'] }}</dd></div>
            <div><dt class="aa-muted">Ready Sections</dt><dd class="text-lg font-semibold">{{ $summary['sections_ready'] }}/{{ $summary['expected_sections'] }}</dd></div>
        </dl>
        @if (! empty($summary['missing_sections']))
            <p class="mt-4 text-sm text-amber-700 dark:text-amber-200">Missing section numbers: {{ implode(', ', $summary['missing_sections']) }}</p>
        @endif
        @if (! $summary['is_complete'])
            <p class="mt-2 text-sm aa-muted">Complete all 4 official sections before publishing this listening test.</p>
        @endif
    </x-ui.card>

    @include('admin.listening.sections.partials.reorder-list')

    <div class="grid gap-4 lg:grid-cols-2">
        @forelse ($sections as $section)
            @include('admin.listening.sections.partials.section-card', ['section' => $section])
        @empty
            <div class="lg:col-span-2">
                <x-ui.empty-state title="No sections yet">Use Auto Create 4 Sections or add sections manually.</x-ui.empty-state>
            </div>
        @endforelse
    </div>
</x-layouts.admin>
