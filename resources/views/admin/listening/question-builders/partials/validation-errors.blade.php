@if ($errors->any())
    <x-ui.alert tone="red" class="mb-4">
        <p class="font-medium">Please fix the following errors:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-ui.alert>
@endif
