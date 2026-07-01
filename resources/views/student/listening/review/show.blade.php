<x-layouts.dashboard :title="$test?->title ?? 'Listening Review'" heading="Listening Review">
    @include('student.listening.review.partials.review-header')
    @include('student.listening.review.partials.visibility-notice')

    <div class="mt-6 grid gap-6 lg:grid-cols-4">
        @include('student.listening.review.partials.section-tabs')
        @include('student.listening.review.partials.question-navigation')
    </div>

    <div class="mt-6">
        <x-ui.card title="Answer Summary">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="py-2 pr-3">Q#</th>
                            <th class="py-2 pr-3">Your answer</th>
                            <th class="py-2 pr-3">Result</th>
                            <th class="py-2 pr-3">Marks</th>
                            <th class="py-2 pr-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr class="border-b" data-question-row data-section="{{ $item['section_number'] ?? 1 }}" data-match="{{ $item['match_status'] ?? '' }}">
                                <td class="py-2 pr-3">{{ $item['question_number'] }}</td>
                                <td class="py-2 pr-3">{{ $item['student_answer'] ?? '—' }}</td>
                                <td class="py-2 pr-3">{{ ucfirst((string) ($item['match_status'] ?? '—')) }}</td>
                                <td class="py-2 pr-3">{{ $item['marks_awarded'] ?? 0 }}/{{ $item['marks_available'] ?? 1 }}</td>
                                <td class="py-2 pr-3">
                                    <x-ui.button size="sm" href="{{ route('student.listening.results.review.question', [$result, $item['question_number']]) }}">Review</x-ui.button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>

    <div class="mt-6">
        <x-ui.button href="{{ route('student.listening.results.show', $result) }}">Back to result</x-ui.button>
    </div>

    @vite(['resources/js/listening/review-navigation.js'])
</x-layouts.dashboard>
