<x-layouts.dashboard
    :heading="$result->attempt->test->title.' — Results'"
    eyebrow="Reading Test Report"
>
    @php
        $stats = $result->statistics;
        $analytics = $result->attempt->readingAnalytics;
    @endphp

    <div class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Overall Band" :value="number_format((float) $result->overall_band, 1)" />
        <x-ui.stat-card label="Raw Score" :value="number_format((float) $result->raw_score, 0).'/'.number_format((float) $result->max_score, 0)" />
        <x-ui.stat-card label="Accuracy" :value="($stats?->accuracy_percent ?? 0).'%'" />
        <x-ui.stat-card label="Answered" :value="($stats?->answered_count ?? 0).'/'.($stats?->total_questions ?? 0)" />
    </div>

    <div class="mb-6 grid gap-6 lg:grid-cols-2">
        <x-ui.card title="Summary">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide aa-muted">Correct</dt>
                    <dd class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stats?->correct_count ?? 0 }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide aa-muted">Incorrect</dt>
                    <dd class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats?->incorrect_count ?? 0 }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide aa-muted">Unanswered</dt>
                    <dd class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $stats?->unanswered_count ?? 0 }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide aa-muted">Flagged</dt>
                    <dd class="mt-1 text-2xl font-bold text-neutral-900 dark:text-white">{{ $stats?->flagged_count ?? 0 }}</dd>
                </div>
            </dl>
            @if ($analytics)
                <p class="mt-4 text-sm aa-muted">
                    Average time per question: {{ gmdate('i:s', $analytics->average_time_seconds ?: 0) }}
                    · Skipped: {{ $analytics->skipped_count }}
                </p>
            @endif
        </x-ui.card>

        <x-ui.card title="Performance by Passage">
            @if (! empty($stats?->by_passage))
                <div class="space-y-3">
                    @foreach ($stats->by_passage as $passage)
                        <div class="rounded-2xl border border-neutral-100 p-4 dark:border-neutral-800">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-semibold">{{ $passage['title'] ?? 'Passage' }}</p>
                                @php
                                    $passageAccuracy = ($passage['max_score'] ?? 0) > 0
                                        ? round((($passage['raw_score'] ?? 0) / $passage['max_score']) * 100, 1)
                                        : 0;
                                @endphp
                                <x-ui.badge tone="blue">{{ $passageAccuracy }}%</x-ui.badge>
                            </div>
                            <p class="mt-1 text-sm aa-muted">
                                {{ $passage['correct'] ?? 0 }}/{{ $passage['total'] ?? 0 }} correct
                            </p>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="aa-muted">No passage breakdown available.</p>
            @endif
        </x-ui.card>
    </div>

    <x-ui.card title="Question-by-Question Report" class="mb-6">
        <x-ui.table>
            <thead>
                <tr class="text-left text-xs uppercase aa-muted">
                    <th class="p-4">Q#</th>
                    <th class="p-4">Type</th>
                    <th class="p-4">Your Answer</th>
                    <th class="p-4">Correct Answer</th>
                    <th class="p-4">Score</th>
                    <th class="p-4">Result</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                @foreach ($result->questionScores as $score)
                    <tr>
                        <td class="p-4 font-semibold">{{ $score->question_number }}</td>
                        <td class="p-4 text-sm aa-muted">{{ $score->question_type->label() }}</td>
                        <td class="p-4 text-sm">{{ $score->student_response ?: '—' }}</td>
                        <td class="p-4 text-sm aa-muted">{{ $score->expected_response ?: '—' }}</td>
                        <td class="p-4 text-sm">{{ number_format((float) $score->score_awarded, 1) }}/{{ number_format((float) $score->max_score, 0) }}</td>
                        <td class="p-4">
                            @if (blank($score->student_response))
                                <x-ui.badge tone="amber">Skipped</x-ui.badge>
                            @elseif ($score->is_correct)
                                <x-ui.badge tone="green">Correct</x-ui.badge>
                            @elseif ((float) $score->partial_ratio > 0)
                                <x-ui.badge tone="blue">Partial</x-ui.badge>
                            @else
                                <x-ui.badge tone="red">Incorrect</x-ui.badge>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>
    </x-ui.card>

    @if ($analytics && ! empty($analytics->heat_map['cells']))
        <x-ui.card title="Performance Heat Map" class="mb-6">
            <x-admin.reading-heat-map
                :cells="$analytics->heat_map['cells']"
                :legend="$analytics->heat_map['legend'] ?? []"
            />
        </x-ui.card>
    @endif

    <div class="flex flex-wrap gap-3">
        <x-ui.button href="{{ route('exam.reading') }}">Take Another Attempt</x-ui.button>
        <x-ui.button href="{{ route('student.dashboard') }}" variant="secondary">Back to Dashboard</x-ui.button>
    </div>
</x-layouts.dashboard>
