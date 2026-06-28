@php
    $groupsRoutePrefix = $groupsRoutePrefix ?? 'admin.listening.tests.sections.groups';
@endphp
@can('create', [\App\Models\Listening\ListeningQuestionGroup::class, $listeningTest, $section])
    <form method="POST" action="{{ route($groupsRoutePrefix.'.store-blank', [$listeningTest, $section]) }}">
        @csrf
        <x-ui.button type="submit" :size="$size ?? 'sm'" :variant="$variant ?? 'primary'">Add Question Group</x-ui.button>
    </form>
@else
    <x-ui.button href="{{ route('admin.listening.tests.builder.index', ['listeningTest' => $listeningTest, 'section' => $section->id]) }}" :size="$size ?? 'sm'" :variant="$variant ?? 'outline'">Add Question Group</x-ui.button>
@endcan
