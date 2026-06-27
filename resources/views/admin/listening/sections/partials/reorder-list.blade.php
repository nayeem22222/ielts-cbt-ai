@if ($sections->isNotEmpty())
    <form method="POST" action="{{ route($sectionsRoutePrefix.'.reorder', $listeningTest) }}" class="mb-6">
        @csrf
        <x-ui.card title="Reorder Sections">
            <ol class="space-y-2">
                @foreach ($sections->sortBy('display_order') as $section)
                    <li class="flex items-center gap-3 rounded-xl border border-neutral-200 px-3 py-2 dark:border-neutral-800">
                        <input type="hidden" name="sections[]" value="{{ $section->id }}">
                        <span class="text-sm font-medium">#{{ $section->display_order }}</span>
                        <span class="text-sm">Section {{ $section->section_number }} — {{ $section->title }}</span>
                    </li>
                @endforeach
            </ol>
            <p class="mt-2 text-xs aa-muted">Reorder via edit display order for now. Submit to persist current order.</p>
            @can('reorder', [App\Models\Listening\ListeningSection::class, $listeningTest])
                <x-ui.button type="submit" class="mt-4" variant="outline" size="sm">Save Order</x-ui.button>
            @endcan
        </x-ui.card>
    </form>
@endif
