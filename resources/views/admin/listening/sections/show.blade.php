<x-layouts.admin :title="$section->title" :heading="$section->title" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Listening Tests', 'href' => route($routePrefix.'.index')], ['label' => $listeningTest->title, 'href' => route($routePrefix.'.show', $listeningTest)], ['label' => 'Sections', 'href' => route($sectionsRoutePrefix.'.index', $listeningTest)], ['label' => $section->title]]">
    @include('admin.listening.sections.partials.alerts')

    <div class="mb-6 flex flex-wrap justify-between gap-4">
        <div>
            <p class="text-sm aa-muted">Section {{ $section->section_number }} · {{ $section->section_type?->label() }}</p>
            <p class="text-sm">Official range: Q{{ $section->start_question_number }}–Q{{ $section->end_question_number }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('update', $section)
                <x-ui.button href="{{ route($sectionsRoutePrefix.'.edit', [$listeningTest, $section]) }}">Edit Section</x-ui.button>
            @endcan
            <x-ui.button href="{{ route($sectionsRoutePrefix.'.index', $listeningTest) }}" variant="outline">Back to Sections</x-ui.button>
            @can('delete', $section)
                <form method="POST" action="{{ route($sectionsRoutePrefix.'.destroy', [$listeningTest, $section]) }}" onsubmit="return confirm('Delete this section?')">
                    @csrf @method('DELETE')
                    <x-ui.button type="submit" variant="danger">Delete Section</x-ui.button>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card title="Section Details">
            <dl class="space-y-3 text-sm">
                <div><dt class="aa-muted">Instruction</dt><dd class="whitespace-pre-wrap">{{ $section->instruction ?: '—' }}</dd></div>
                <div><dt class="aa-muted">Audio</dt><dd>{{ $section->audio?->original_name ?? '—' }}</dd></div>
                <div><dt class="aa-muted">Duration</dt><dd>{{ $section->duration_seconds ? $section->duration_seconds.' seconds' : '—' }}</dd></div>
                <div><dt class="aa-muted">Preparation</dt><dd>{{ $section->preparation_seconds ? $section->preparation_seconds.' seconds' : '—' }}</dd></div>
                <div><dt class="aa-muted">Status</dt><dd>{{ $section->is_active ? 'Active' : 'Inactive' }}</dd></div>
            </dl>
        </x-ui.card>
        @include('admin.listening.sections.partials.readiness-card')
    </div>

    <div class="mt-6">
        @include('admin.listening.sections.partials.transcript-card')
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2 xl:grid-cols-4">
        <x-ui.card title="Question Groups"><p class="text-sm aa-muted">Question group builder coming in a later volume.</p><x-ui.button class="mt-4" variant="outline" disabled>Manage Groups</x-ui.button></x-ui.card>
        <x-ui.card title="Questions"><p class="text-sm aa-muted">Question builder coming in a later volume.</p><x-ui.button class="mt-4" variant="outline" disabled>Manage Questions</x-ui.button></x-ui.card>
        <x-ui.card title="Audio Timeline"><p class="text-sm aa-muted">Audio timeline coming in a later volume.</p></x-ui.card>
        <x-ui.card title="Student Preview"><p class="text-sm aa-muted">Student preview coming in a later volume.</p></x-ui.card>
    </div>
</x-layouts.admin>
