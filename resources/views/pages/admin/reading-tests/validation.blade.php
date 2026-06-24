<x-layouts.admin
    :title="$test->title.' — Validation'"
    :heading="$test->title"
    eyebrow="Reading Test Validation"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard')],
        ['label' => 'Reading Tests', 'href' => route('admin.reading-tests.index')],
        ['label' => $test->title, 'href' => route('admin.reading-tests.builder', $test)],
        ['label' => 'Validation'],
    ]"
>
    <div class="mb-6 flex flex-wrap gap-2">
        <form method="POST" action="{{ route('admin.reading-tests.validate', $test) }}">
            @csrf
            <x-ui.button type="submit">Run Validation</x-ui.button>
        </form>
        <x-ui.button href="{{ route('admin.reading-tests.preview-full', $test) }}" variant="outline">Full Preview</x-ui.button>
        <x-ui.button href="{{ route('admin.reading-tests.builder', $test) }}" variant="outline">Back to Builder</x-ui.button>
    </div>

    @if (session('status'))
        <x-ui.alert tone="green" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    @if (session('error'))
        <x-ui.alert tone="red" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    @include('pages.admin.reading-tests.partials.validation-panel', ['result' => $result, 'test' => $test])

    @if (($result['errors'] ?? []) !== [])
        <x-ui.card title="Validation Errors" class="mt-6">
            <div class="space-y-3">
                @foreach ($result['errors'] as $error)
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm dark:border-red-900 dark:bg-red-950/30">
                        <p class="font-medium text-red-800 dark:text-red-200">{{ $error['message'] }}</p>
                        <p class="mt-1 text-xs aa-muted">{{ $error['entity'] }} #{{ $error['entity_id'] }} · {{ $error['type'] }}</p>
                        <p class="mt-2 text-sm">{{ $error['suggested_fix'] }}</p>
                        @if (! empty($error['section_link']))
                            <x-ui.button href="{{ $error['section_link'] }}" size="sm" variant="outline" class="mt-2">Open Section</x-ui.button>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    @endif

    @if (($result['warnings'] ?? []) !== [])
        <x-ui.card title="Warnings" class="mt-6">
            <div class="space-y-3">
                @foreach ($result['warnings'] as $warning)
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm dark:border-amber-900 dark:bg-amber-950/30">
                        <p class="font-medium text-amber-900 dark:text-amber-100">{{ $warning['message'] }}</p>
                        <p class="mt-2 text-sm">{{ $warning['suggested_fix'] }}</p>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    @endif
</x-layouts.admin>
