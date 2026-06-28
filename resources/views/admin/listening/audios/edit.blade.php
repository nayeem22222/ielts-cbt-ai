<x-layouts.admin title="Edit Listening Audio" heading="Edit Listening Audio" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Listening Audio', 'href' => route($routePrefix.'.index')], ['label' => $audio->original_name]]">
    @include('admin.listening.sections.partials.alerts')

    <x-ui.card title="Audio Details">
        <form method="POST" action="{{ route($routePrefix.'.update', $audio) }}" class="space-y-4">
            @csrf @method('PUT')
            <x-ui.input name="title" label="Title" :value="old('title', $audio->title())" />
            <x-ui.textarea name="description" label="Description" rows="3">{{ old('description', $audio->description()) }}</x-ui.textarea>
            <div class="flex gap-2">
                <x-ui.button type="submit">Save</x-ui.button>
                <x-ui.button href="{{ route($routePrefix.'.show', $audio) }}" variant="outline">Cancel</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-layouts.admin>
