<x-ui.card title="Admin Preview — {{ $type->label() }}">
    @if ($type->value === 'matching_information')
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-neutral-200 dark:border-neutral-700">
                        <th class="p-2 text-left">#</th>
                        <th class="p-2 text-left">Statement</th>
                        @foreach ($options as $option)
                            <th class="p-2 text-center">{{ $option->option_key }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($questions as $question)
                        <tr class="border-b border-neutral-100 dark:border-neutral-800">
                            <td class="p-2 font-semibold">{{ $question->question_number }}</td>
                            <td class="p-2">{{ $question->prompt }}</td>
                            @foreach ($options as $option)
                                <td class="p-2 text-center">
                                    @if ($question->correctAnswers->first()?->answer === $option->option_key)
                                        <span class="font-bold text-brand-600">✓</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @elseif ($type->value === 'matching_headings')
        <div class="grid gap-6 lg:grid-cols-2">
            <div>
                <h4 class="mb-2 font-semibold">List of Headings</h4>
                <ul class="space-y-1 text-sm">
                    @foreach ($options as $option)
                        <li><span class="font-semibold">{{ $option->option_key }}.</span> {{ $option->option_label }}</li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h4 class="mb-2 font-semibold">Paragraphs</h4>
                <ul class="space-y-2 text-sm">
                    @foreach ($questions as $question)
                        <li class="flex justify-between gap-3 rounded-lg border border-neutral-200 p-2 dark:border-neutral-700">
                            <span>{{ $question->prompt }}</span>
                            <span class="font-semibold">{{ $question->correctAnswers->first()?->answer ?? '—' }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @elseif (in_array($type->value, ['matching_features', 'matching_people', 'dropdown'], true))
        <div class="grid gap-6 lg:grid-cols-2">
            <div>
                <h4 class="mb-2 font-semibold">{{ $type->value === 'matching_people' ? 'People' : 'Options' }}</h4>
                <ul class="space-y-1 text-sm">
                    @foreach ($options as $option)
                        <li><span class="font-semibold">{{ $option->option_key }}.</span> {{ $option->option_label ?: '—' }}</li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h4 class="mb-2 font-semibold">Statements</h4>
                <ul class="space-y-2 text-sm">
                    @foreach ($questions as $question)
                        <li class="flex justify-between gap-3 rounded-lg border border-neutral-200 p-2 dark:border-neutral-700">
                            <span><strong>{{ $question->question_number }}.</strong> {{ $question->prompt }}</span>
                            <span class="font-semibold">{{ $question->correctAnswers->first()?->answer ?? '—' }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @elseif ($type->value === 'matching_sentence_endings')
        <div class="grid gap-6 lg:grid-cols-2">
            <div>
                <h4 class="mb-2 font-semibold">Sentence Beginnings</h4>
                <ul class="space-y-2 text-sm">
                    @foreach ($questions as $question)
                        <li><strong>{{ $question->question_number }}.</strong> {{ $question->prompt }} <span class="font-semibold text-brand-600">{{ $question->correctAnswers->first()?->answer }}</span></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h4 class="mb-2 font-semibold">Endings</h4>
                <ul class="space-y-1 text-sm">
                    @foreach ($options as $option)
                        <li><span class="font-semibold">{{ $option->option_key }}.</span> {{ $option->option_label }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
</x-ui.card>
