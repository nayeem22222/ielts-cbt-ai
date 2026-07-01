<x-layouts.admin :title="'Result '.$result->result_code" :heading="$result->test?->title ?? 'Listening Result'" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Listening'], ['label' => 'Results', 'href' => route('admin.listening.results.index')], ['label' => $result->result_code ?? 'Detail']]">
    @if (session('status'))
        <div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        @include('admin.listening.results.partials.student-info')
        @include('admin.listening.results.partials.attempt-info')
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        @include('admin.listening.results.partials.evaluation-info')
        @include('admin.listening.results.partials.score-card')
    </div>

    <div class="mt-6">
        @include('admin.listening.results.partials.section-breakdown')
    </div>

    <div class="mt-6">
        @include('admin.listening.results.partials.question-summary-admin')
    </div>

    <div class="mt-6">
        @include('admin.listening.results.partials.actions')
    </div>
</x-layouts.admin>
