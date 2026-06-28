<x-ui.card title="Section {{ $section->section_number }}" :subtitle="$section->title">
    <dl class="grid gap-3 text-sm sm:grid-cols-2">
        <div><dt class="aa-muted">Official Range</dt><dd class="font-medium">Q{{ $section->start_question_number }}–Q{{ $section->end_question_number }}</dd></div>
        <div><dt class="aa-muted">Type</dt><dd>{{ $section->section_type?->label() }}</dd></div>
        <div><dt class="aa-muted">Audio</dt><dd>{{ $section->audio_id ? ($section->audio?->original_name ?? 'Attached') : 'Not attached' }}</dd></div>
        <div><dt class="aa-muted">Transcript</dt><dd>{{ $section->transcript_id ? ($section->transcript?->title ?? 'Attached') : 'Not attached' }}</dd></div>
        <div><dt class="aa-muted">Groups</dt><dd>{{ $section->questionGroups->count() }}</dd></div>
        <div><dt class="aa-muted">Questions</dt><dd>{{ $section->questions_count ?? 0 }}/{{ $section->total_questions }}</dd></div>
    </dl>

    <p class="mt-4 text-sm aa-muted">Audio and transcript are optional for question building. Attach them from the section page when ready.</p>

    <div class="mt-4 flex flex-wrap gap-2">
        @can('create', [\App\Models\Listening\ListeningQuestionGroup::class, $listeningTest, $section])
            <form method="POST" action="{{ route('admin.listening.tests.sections.groups.store-blank', [$listeningTest, $section]) }}">
                @csrf
                <x-ui.button type="submit">Add Question Group</x-ui.button>
            </form>
        @endcan
        <x-ui.button href="{{ route('admin.listening.tests.sections.show', [$listeningTest, $section]) }}" variant="outline">Section Settings</x-ui.button>
    </div>
</x-ui.card>

@if ($section->questionGroups->isEmpty())
    <x-ui.empty-state title="No question groups yet">
        No question groups yet. Add your first group to start building Q{{ $section->start_question_number }}–Q{{ $section->end_question_number }}.
    </x-ui.empty-state>
@endif
