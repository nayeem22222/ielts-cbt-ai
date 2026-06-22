<x-layouts.admin :title="$test->title.' Builder'" :heading="$test->title" eyebrow="Reading Test Builder" :breadcrumbs="[['label' => 'Dashboard', 'href' => route('admin.dashboard')], ['label' => 'Reading Tests', 'href' => route('admin.reading-tests.index')], ['label' => 'Builder']]">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-neutral-900 dark:text-white">{{ $test->title }}</h2>
            <p class="text-sm aa-muted">{{ $sections->count() }} passages · {{ $test->total_questions ?? 0 }} questions · {{ gmdate('H:i:s', $test->duration_seconds ?: 3600) }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-ui.button href="{{ route('admin.reading-tests.preview', $test) }}" variant="outline">Preview</x-ui.button>
            <x-ui.button href="{{ route('admin.reading-tests.export-json', $test) }}" variant="outline">Export JSON</x-ui.button>
            <x-ui.button href="{{ route('admin.reading-tests.edit', $test) }}" variant="outline">Settings</x-ui.button>
        </div>
    </div>

    @if (session('status'))
        <x-ui.alert class="mb-6">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <x-ui.card title="Import JSON" class="xl:col-span-3">
            <form method="POST" action="{{ route('admin.reading-tests.import-json', $test) }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-4">
                @csrf
                <x-ui.input type="file" name="file" label="Import passages & questions" accept=".json,application/json" required class="min-w-64" />
                <x-ui.button type="submit">Import</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card title="Add Passage" class="xl:col-span-1">
            <form method="POST" action="{{ route('admin.reading-tests.passages.store', $test) }}" class="space-y-4">
                @csrf
                <x-ui.input name="title" label="Passage Title" required />
                <x-ui.input name="sort_order" type="number" label="Sort Order" value="{{ $sections->count() + 1 }}" />
                <x-ui.textarea name="instructions" label="Instructions" rows="2"></x-ui.textarea>
                <x-ui.rich-editor name="stimulus_text" label="Passage Text" />
                <x-ui.button type="submit">Add Passage</x-ui.button>
            </form>
        </x-ui.card>

        <div class="space-y-6 xl:col-span-2">
            @forelse ($sections as $section)
                <x-ui.card :title="$section->title">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <x-ui.badge tone="blue">{{ $section->question_count ?? 0 }} questions</x-ui.badge>
                        <span class="text-xs aa-muted">Passage {{ $section->sort_order }}</span>
                    </div>

                    <form method="POST" action="{{ route('admin.reading-tests.passages.update', [$test, $section]) }}" class="space-y-4 border-b border-neutral-100 pb-6 dark:border-neutral-800">
                        @csrf @method('PUT')
                        <x-ui.input name="title" label="Title" :value="$section->title" required />
                        <x-ui.input name="sort_order" type="number" label="Sort Order" :value="$section->sort_order" />
                        <x-ui.textarea name="instructions" label="Instructions" rows="2">{{ $section->instructions }}</x-ui.textarea>
                        <x-ui.rich-editor name="stimulus_text" label="Passage Text" :value="$section->stimulus_text" />
                        <x-ui.button type="submit" variant="outline">Update Passage</x-ui.button>
                    </form>

                    @if ($section->testQuestions->isNotEmpty())
                        <div class="mt-6 space-y-3">
                            <h4 class="font-semibold text-neutral-900 dark:text-white">Questions</h4>
                            @foreach ($section->testQuestions as $pivot)
                                @php $question = $pivot->question; @endphp
                                <details class="rounded-2xl border border-neutral-200 p-4 dark:border-neutral-800">
                                    <summary class="cursor-pointer font-medium">
                                        Q{{ $question->question_number }} · {{ $question->type->label() }}
                                    </summary>
                                    <form method="POST" action="{{ route('admin.reading-tests.questions.update', [$test, $question]) }}" class="mt-4 space-y-4">
                                        @csrf @method('PUT')
                                        @include('pages.admin.reading-tests.partials.question-fields', ['questionTypes' => $questionTypes, 'question' => $question])
                                        <div class="flex gap-2">
                                            <x-ui.button type="submit">Update Question</x-ui.button>
                                        </div>
                                    </form>
                                    <form method="POST" action="{{ route('admin.reading-tests.questions.destroy', [$test, $section, $question]) }}" class="mt-3" onsubmit="return confirm('Remove this question?')">
                                        @csrf @method('DELETE')
                                        <x-ui.button type="submit" size="sm" variant="danger">Remove</x-ui.button>
                                    </form>
                                </details>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.reading-tests.questions.store', [$test, $section]) }}" class="mt-6 space-y-4 border-t border-neutral-100 pt-6 dark:border-neutral-800">
                        @csrf
                        <h4 class="font-semibold text-neutral-900 dark:text-white">Add Question</h4>
                        @include('pages.admin.reading-tests.partials.question-fields', ['questionTypes' => $questionTypes])
                        <x-ui.button type="submit">Add Question</x-ui.button>
                    </form>
                </x-ui.card>
            @empty
                <x-ui.empty-state title="No passages yet">Add your first reading passage to begin building questions.</x-ui.empty-state>
            @endforelse
        </div>
    </div>
</x-layouts.admin>
