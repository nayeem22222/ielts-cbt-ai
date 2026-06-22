@props([
    'questions' => [],
])

<div class="grid grid-cols-5 gap-2 sm:grid-cols-8 lg:grid-cols-5 xl:grid-cols-8">
    @foreach ($questions as $question)
        @php
            $classes = 'h-9 rounded-xl text-sm font-semibold transition ';
            if ($question['active'] ?? false) {
                $classes .= 'bg-brand-500 text-white ring-2 ring-brand-300';
            } elseif ($question['flagged'] ?? false) {
                $classes .= 'bg-amber-100 text-amber-800 ring-1 ring-amber-300 dark:bg-amber-500/20 dark:text-amber-200';
            } elseif ($question['answered'] ?? false) {
                $classes .= 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300';
            } else {
                $classes .= 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300';
            }
        @endphp
        <button type="button" @click="selectQuestion({{ $question['id'] }})" class="{{ $classes }}">
            {{ $question['number'] }}
        </button>
    @endforeach
</div>
