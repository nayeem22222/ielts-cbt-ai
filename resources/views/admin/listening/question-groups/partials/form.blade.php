@include('admin.listening.question-groups.partials.form-fields')

<div class="mt-6 flex flex-wrap gap-3">
    <x-ui.button type="submit">{{ $submitLabel }}</x-ui.button>
    <x-ui.button href="{{ route('admin.listening.tests.builder.index', ['listeningTest' => $listeningTest, 'section' => $section->id, 'question_group' => $group->id ?? null]) }}" variant="outline">Cancel</x-ui.button>
</div>
