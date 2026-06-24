<x-layouts.admin :title="$test->title.' Preview'" :heading="$test->title" eyebrow="Reading Test Preview" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Reading Tests', 'href' => route('admin.reading-tests.index')], ['label' => 'Preview']]">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-neutral-900 dark:text-white">Candidate Preview</h2>
            <p class="text-sm aa-muted">{{ $test->exam_type?->label() }} · {{ $test->total_questions ?? 0 }} questions</p>
        </div>
        <div class="flex gap-2">
            @if ($test->status === \App\Enums\Course\PublishStatus::Published)
                <x-ui.button href="{{ route('exam.reading.show', $test) }}" target="_blank">Open Player</x-ui.button>
            @endif
            <x-ui.button href="{{ route('admin.reading-tests.builder', $test) }}" variant="outline">Back to Builder</x-ui.button>
            <x-ui.button href="{{ route('admin.reading-tests.export-json', $test) }}" variant="outline">Export JSON</x-ui.button>
        </div>
    </div>

    <div class="space-y-8">
        @forelse ($sections as $section)
            <x-ui.card :title="'Passage '.$section->sort_order.': '.$section->title">
                @if ($section->instructions)
                    <p class="mb-4 rounded-2xl bg-neutral-50 p-4 text-sm aa-muted dark:bg-neutral-900">{{ $section->instructions }}</p>
                @endif

                @if ($section->stimulus_text)
                    <div class="prose prose-neutral mb-6 max-w-none dark:prose-invert">{!! nl2br(e($section->stimulus_text)) !!}</div>
                @endif

                <div class="space-y-6">
                    @foreach ($section->testQuestions as $pivot)
                        @php $question = $pivot->question; @endphp
                        <div class="rounded-2xl border border-neutral-200 p-5 dark:border-neutral-800">
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <span class="text-sm font-semibold text-brand-600">Question {{ $question->question_number }}</span>
                                <x-ui.badge tone="blue">{{ $question->type->label() }}</x-ui.badge>
                            </div>
                            <div class="mb-4 text-sm leading-7">{!! nl2br(e($question->prompt)) !!}</div>

                            @if ($question->options->isNotEmpty())
                                <div class="space-y-2">
                                    @foreach ($question->options as $option)
                                        <label class="flex items-start gap-3 rounded-xl border border-neutral-200 p-3 text-sm dark:border-neutral-800">
                                            <input type="radio" disabled class="mt-1">
                                            <span><strong>{{ $option->label }}.</strong> {{ $option->option_text }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <x-ui.input disabled placeholder="Candidate answer" />
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        @empty
            <x-ui.empty-state title="Nothing to preview">Add passages and questions in the builder first.</x-ui.empty-state>
        @endforelse
    </div>
</x-layouts.admin>
