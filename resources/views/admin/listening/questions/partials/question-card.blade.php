<div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-neutral-200 p-4 dark:border-neutral-800">
    <div>
        <p class="font-medium">Q{{ $question->question_number }} · {{ $question->question_type?->label() }}</p>
        <p class="text-sm aa-muted">{{ \Illuminate\Support\Str::limit($question->question_text ?? 'No text', 80) }}</p>
        @php
            $hasAnswer = is_array($question->correct_answer) && collect($question->correct_answer)->contains(fn ($a) => is_array($a) ? filled($a['value'] ?? null) : filled($a));
        @endphp
        <p class="mt-1 text-xs {{ $hasAnswer ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300' }}">
            {{ $hasAnswer ? 'Answer set' : 'Answer missing' }}
        </p>
    </div>
    <div class="flex gap-2">
        <x-ui.button href="{{ route($questionsRoutePrefix.'.show', [$listeningTest, $section, $group, $question]) }}" size="sm" variant="outline">View</x-ui.button>
        <x-ui.button href="{{ route($questionsRoutePrefix.'.edit', [$listeningTest, $section, $group, $question]) }}" size="sm" variant="outline">Edit</x-ui.button>
        @can('delete', $question)
            <form method="POST" action="{{ route($questionsRoutePrefix.'.destroy', [$listeningTest, $section, $group, $question]) }}" onsubmit="return confirm('Delete this question?')">
                @csrf
                @method('DELETE')
                <x-ui.button type="submit" size="sm" variant="danger">Delete</x-ui.button>
            </form>
        @endcan
    </div>
</div>
