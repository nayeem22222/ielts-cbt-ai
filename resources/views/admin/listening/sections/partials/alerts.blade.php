@if (session('status'))
    <x-ui.alert tone="green" class="mb-4">{{ session('status') }}</x-ui.alert>
@endif

@if (session('error'))
    <x-ui.alert tone="red" class="mb-4">{{ session('error') }}</x-ui.alert>
@endif

@if ($errors->any())
    <x-ui.alert tone="red" class="mb-4" title="Please fix the following errors:">
        <ul class="list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-ui.alert>
@endif
