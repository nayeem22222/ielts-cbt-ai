<x-ui.card title="Question Group Editor" :subtitle="$group->title">
    <form
        method="POST"
        action="{{ route('admin.listening.tests.sections.groups.update', [$listeningTest, $section, $group]) }}"
        class="space-y-5"
    >
        @csrf
        @method('PUT')

        @include('admin.listening.question-groups.partials.form-fields-metadata')

        <div class="flex flex-wrap gap-2">
            <x-ui.button type="submit">Save Question Group</x-ui.button>
            <x-ui.button href="{{ \App\Support\Listening\ListeningQuestionBuilderRoutes::manageQuestionsUrl($group) }}" variant="secondary">Manage Questions</x-ui.button>
            @can('delete', $group)
                <x-ui.button type="button" variant="outline" @click="groupDeleteOpen = true">Delete</x-ui.button>
            @endcan
        </div>
    </form>
</x-ui.card>

@can('create', [\App\Models\Listening\ListeningQuestionGroup::class, $listeningTest, $section])
    <div class="mt-3">
        <form method="POST" action="{{ route('admin.listening.tests.sections.groups.duplicate', [$listeningTest, $section, $group]) }}">
            @csrf
            <x-ui.button type="submit" variant="outline">Duplicate Group</x-ui.button>
        </form>
    </div>
@endcan

<x-ui.card title="Group Preview" class="mt-4">
    <div class="rounded-3xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-950">
        <div class="mx-auto max-w-3xl space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-950 dark:text-blue-200" x-text="groupQuestionTypeLabel || '{{ $group->question_type?->label() }}'"></span>
                <span class="inline-flex items-center rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs font-medium text-neutral-700 dark:bg-neutral-800 dark:text-neutral-200">
                    Questions <span x-text="groupRangeLabel()"></span>
                </span>
            </div>
            <h3 class="text-xl font-bold text-neutral-900 dark:text-white" x-text="groupTitle || '{{ $group->title }}'"></h3>
            <p class="text-sm italic leading-7 text-neutral-600 dark:text-neutral-300" x-text="groupInstruction || '{{ $group->instruction }}'"></p>
            <p class="text-xs aa-muted">Configure type-specific content and answers in <strong>Manage Questions</strong>.</p>
        </div>
    </div>
</x-ui.card>

@can('delete', $group)
    <div x-show="groupDeleteOpen" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-neutral-950/50 p-4">
        <div @click.outside="groupDeleteOpen = false" class="aa-card w-full max-w-lg p-6">
            <h3 class="text-lg font-semibold text-neutral-900 dark:text-white">Delete Question Group?</h3>
            <p class="mt-2 text-sm aa-muted">This will permanently delete <strong>{{ $group->title }}</strong> and all questions in this group.</p>
            <div class="mt-6 flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.listening.tests.sections.groups.destroy', [$listeningTest, $section, $group]) }}">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger">Delete Group</x-ui.button>
                </form>
                <x-ui.button type="button" variant="outline" @click="groupDeleteOpen = false">Cancel</x-ui.button>
            </div>
        </div>
    </div>
@endcan
