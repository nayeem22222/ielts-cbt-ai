<x-ui.card title="Actions">
    <div class="flex flex-wrap gap-3">
        @can('publish', $result)
            <form method="POST" action="{{ route('admin.listening.results.publish', $result) }}">
                @csrf
                <x-ui.button type="submit">Publish</x-ui.button>
            </form>
        @endcan
        @can('hide', $result)
            <form method="POST" action="{{ route('admin.listening.results.hide', $result) }}">
                @csrf
                <x-ui.button type="submit" variant="secondary">Hide</x-ui.button>
            </form>
        @endcan
        @can('rebuild', $result)
            <form method="POST" action="{{ route('admin.listening.results.rebuild', $result) }}">
                @csrf
                <x-ui.button type="submit" variant="secondary">Rebuild</x-ui.button>
            </form>
        @endcan
        <x-ui.button variant="secondary" href="{{ route('admin.listening.results.index') }}">Back</x-ui.button>
    </div>
</x-ui.card>
