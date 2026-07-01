<x-layouts.admin :title="'Review '.$result->result_code" heading="Listening Review" eyebrow="IELTS CBT" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Results', 'href' => route('admin.listening.results.index')], ['label' => 'Review']]">
    @if (session('status'))
        <div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    @include('admin.listening.review.partials.review-header-admin')

    <x-ui.card title="Review Items" class="mt-6">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b text-left">
                    <th class="py-2 pr-2">Q#</th>
                    <th class="py-2 pr-2">Student</th>
                    <th class="py-2 pr-2">Match</th>
                    <th class="py-2 pr-2">Marks</th>
                    <th class="py-2 pr-2"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr class="border-b">
                        <td class="py-2 pr-2">{{ $item['question_number'] }}</td>
                        <td class="py-2 pr-2">{{ json_encode($item['student_answer_snapshot'] ?? null) }}</td>
                        <td class="py-2 pr-2">{{ $item['match_status'] }} / {{ $item['match_reason'] ?? '—' }}</td>
                        <td class="py-2 pr-2">{{ $item['marks_awarded'] }}/{{ $item['marks_available'] }}</td>
                        <td class="py-2 pr-2">
                            <x-ui.button size="sm" href="{{ route('admin.listening.results.review.question', [$result, $item['question_number']]) }}">Debug</x-ui.button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-ui.card>

    <form method="POST" action="{{ route('admin.listening.results.review.rebuild', $result) }}" class="mt-6">
        @csrf
        <x-ui.button type="submit">Rebuild review items</x-ui.button>
    </form>
</x-layouts.admin>
