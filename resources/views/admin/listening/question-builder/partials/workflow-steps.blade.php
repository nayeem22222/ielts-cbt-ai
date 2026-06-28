@php
    $current = $current ?? 'section';
    $steps = [
        'section' => ['label' => 'Add question groups', 'hint' => 'Choose a type and question range for this section.'],
        'group' => ['label' => 'Create questions', 'hint' => 'Bulk-create placeholders for the whole range, then fill in answers.'],
        'questions' => ['label' => 'Set answers', 'hint' => 'Open each question and enter the correct answer.'],
        'done' => ['label' => 'Check readiness', 'hint' => 'Confirm every question number is filled before publishing.'],
    ];
@endphp
<div class="mb-6 rounded-2xl border border-blue-100 bg-blue-50/70 p-4 dark:border-blue-900/40 dark:bg-blue-950/20">
    <p class="mb-3 text-sm font-semibold text-blue-900 dark:text-blue-100">How to build this section</p>
    <ol class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($steps as $key => $step)
            <li class="rounded-xl border border-white/60 bg-white/80 p-3 text-sm dark:border-neutral-800 dark:bg-neutral-900/60 {{ $current === $key ? 'ring-2 ring-brand-500' : '' }}">
                <p class="font-medium">{{ $loop->iteration }}. {{ $step['label'] }}</p>
                <p class="mt-1 text-xs aa-muted">{{ $step['hint'] }}</p>
            </li>
        @endforeach
    </ol>
</div>
