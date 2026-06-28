<div class="mb-4 flex flex-wrap gap-2">
    <x-ui.button href="{{ route($builderRoutePrefix.'.index', $listeningTest) }}" size="sm" variant="outline">Test Builder</x-ui.button>
    @if (! empty($section))
        <x-ui.button href="{{ route($sectionsRoutePrefix.'.builder.index', [$listeningTest, $section]) }}" size="sm" variant="outline">Section Builder</x-ui.button>
    @endif
    @if (! empty($group))
        <x-ui.button href="{{ route($groupsRoutePrefix.'.show', [$listeningTest, $section, $group]) }}" size="sm" variant="outline">Group Overview</x-ui.button>
        <x-ui.button href="{{ route($questionsRoutePrefix.'.index', [$listeningTest, $section, $group]) }}" size="sm" variant="outline">Questions</x-ui.button>
    @endif
</div>
