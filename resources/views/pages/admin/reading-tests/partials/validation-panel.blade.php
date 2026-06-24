@props(['result', 'test'])

@php
    $summary = $result['summary'] ?? [];
    $isValid = (bool) ($result['is_valid'] ?? false);
@endphp

<x-ui.card title="Validation Summary">
    <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 text-sm">
        <div>
            <dt class="aa-muted">Passages</dt>
            <dd class="text-lg font-semibold">{{ $summary['passages'] ?? 0 }}</dd>
        </div>
        <div>
            <dt class="aa-muted">Question Groups</dt>
            <dd class="text-lg font-semibold">{{ $summary['groups'] ?? 0 }}</dd>
        </div>
        <div>
            <dt class="aa-muted">Questions</dt>
            <dd class="text-lg font-semibold">{{ $summary['questions'] ?? 0 }}</dd>
        </div>
        <div>
            <dt class="aa-muted">Missing Answers</dt>
            <dd class="text-lg font-semibold {{ ($summary['missing_answers'] ?? 0) > 0 ? 'text-red-600' : '' }}">{{ $summary['missing_answers'] ?? 0 }}</dd>
        </div>
        <div>
            <dt class="aa-muted">Duplicate Numbers</dt>
            <dd class="text-lg font-semibold {{ ($summary['duplicates'] ?? 0) > 0 ? 'text-red-600' : '' }}">{{ $summary['duplicates'] ?? 0 }}</dd>
        </div>
        <div>
            <dt class="aa-muted">Publish Readiness</dt>
            <dd>
                <x-ui.badge :tone="$isValid ? 'green' : 'red'">{{ $isValid ? 'Ready' : 'Blocked' }}</x-ui.badge>
            </dd>
        </div>
    </dl>

    <div class="mt-4 flex flex-wrap gap-2">
        <form method="POST" action="{{ route('admin.reading-tests.validate', $test) }}">
            @csrf
            <x-ui.button type="submit" size="sm" variant="outline">Run Validation</x-ui.button>
        </form>
        <x-ui.button href="{{ route('admin.reading-tests.validation', $test) }}" size="sm" variant="outline">Validation Dashboard</x-ui.button>
        <x-ui.button href="{{ route('admin.reading-tests.preview-full', $test) }}" size="sm" variant="outline">Full Preview</x-ui.button>
    </div>
</x-ui.card>
