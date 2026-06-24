@props([
    'test',
    'selectedPassage',
    'passageStatuses',
])

<x-ui.card title="Passage Editor" :subtitle="'Passage '.$selectedPassage->part_number">
    <form
        method="POST"
        action="{{ route('admin.reading-tests.passages.update', [$test, $selectedPassage]) }}"
        class="space-y-5"
        @submit="syncEditorBeforeSubmit()"
    >
        @csrf
        @method('PUT')

        <div class="grid gap-4 md:grid-cols-2">
            <x-ui.input name="title" label="Title" :value="old('title', $selectedPassage->title)" required class="md:col-span-2" />
            <x-ui.input name="subtitle" label="Subtitle" :value="old('subtitle', $selectedPassage->subtitle)" class="md:col-span-2" />
            <x-ui.input name="part_number_display" label="Passage Number" :value="'Passage '.$selectedPassage->part_number" disabled />
            <x-ui.input name="sort_order" type="number" min="1" label="Sort Order" :value="old('sort_order', $selectedPassage->sort_order)" />
            <x-ui.input name="start_question" type="number" min="1" label="Start Question" :value="old('start_question', $selectedPassage->start_question)" required />
            <x-ui.input name="end_question" type="number" min="1" label="End Question" :value="old('end_question', $selectedPassage->end_question)" required />
            <div class="md:col-span-2 rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm dark:border-neutral-800 dark:bg-neutral-900">
                <span class="aa-muted">Question Range:</span>
                <span class="font-semibold">{{ old('start_question', $selectedPassage->start_question) }}-{{ old('end_question', $selectedPassage->end_question) }}</span>
            </div>
            <x-ui.select name="status" label="Status" class="md:col-span-2">
                @foreach ($passageStatuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $selectedPassage->status?->value ?? 'draft') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </x-ui.select>
        </div>

        <x-ui.textarea name="instruction" label="Instruction" rows="3" help="Example: You should spend about 20 minutes on Questions 1–13, which are based on Reading Passage 1 below.">{{ old('instruction', $selectedPassage->instruction) }}</x-ui.textarea>

        <div class="space-y-2">
            <label for="content_html" class="block text-sm font-medium text-neutral-700 dark:text-neutral-200">Content</label>
            <textarea id="content_html" name="content_html" class="hidden">{{ old('content_html', $selectedPassage->content_html) }}</textarea>
            <p class="text-xs aa-muted">Paste directly from IELTS PDFs. Formatting, lists, and paragraph breaks are preserved.</p>
        </div>

        <label class="flex items-center gap-3 text-sm font-medium text-neutral-700 dark:text-neutral-200">
            <input type="hidden" name="auto_paragraph_labels" value="0">
            <input
                type="checkbox"
                name="auto_paragraph_labels"
                value="1"
                class="rounded border-neutral-300"
                x-model="autoLabels"
                @checked(old('auto_paragraph_labels', $selectedPassage->auto_paragraph_labels ? '1' : '0') === '1' || old('auto_paragraph_labels', $selectedPassage->auto_paragraph_labels))
            >
            Auto Paragraph Labels (A, B, C, …)
        </label>

        @if ($errors->any())
            <x-ui.alert tone="red">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <div class="flex flex-wrap gap-2">
            <x-ui.button type="submit">Save Passage</x-ui.button>
            <x-ui.button type="button" variant="outline" @click="deleteOpen = true">Delete</x-ui.button>
        </div>
    </form>
</x-ui.card>

<x-ui.card title="Passage Preview">
    <div class="rounded-3xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-950">
        <article class="reading-passage-preview mx-auto max-w-3xl">
            <h2 class="mb-2 text-center text-xl font-bold text-neutral-900 dark:text-white">{{ $selectedPassage->title }}</h2>
            @if ($selectedPassage->subtitle)
                <p class="mb-4 text-center text-sm font-medium text-neutral-600 dark:text-neutral-300">{{ $selectedPassage->subtitle }}</p>
            @endif
            @if ($selectedPassage->instruction)
                <p class="mb-6 text-sm italic leading-7 text-neutral-600 dark:text-neutral-300">{{ $selectedPassage->instruction }}</p>
            @endif
            <div
                class="reading-passage-preview-body font-serif text-[15px] leading-8 text-neutral-900 dark:text-neutral-100 [&_.reading-passage-label]:font-bold [&_h2]:mb-3 [&_h2]:text-xl [&_h2]:font-bold [&_ol]:my-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_p]:mb-4 [&_ul]:my-4 [&_ul]:list-disc [&_ul]:pl-6"
                x-html="previewContent()"
            ></div>
        </article>
    </div>
</x-ui.card>

<div x-show="deleteOpen" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-neutral-950/50 p-4">
    <div @click.outside="deleteOpen = false" class="aa-card w-full max-w-lg p-6">
        <h3 class="text-lg font-semibold text-neutral-900 dark:text-white">Delete Passage?</h3>
        <p class="mt-3 text-sm aa-muted">
            You are about to delete <strong>{{ $selectedPassage->title }}</strong>.
            This passage has <strong>{{ $selectedPassage->questions_count ?? 0 }}</strong> linked questions across {{ $selectedPassage->groups_count ?? $selectedPassage->groups->count() }} question groups.
        </p>
        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
            Deleting this passage will also remove all linked Question Groups and Questions.
        </p>
        <div class="mt-6 flex justify-end gap-2">
            <x-ui.button type="button" variant="outline" @click="deleteOpen = false">Cancel</x-ui.button>
            <form method="POST" action="{{ route('admin.reading-tests.passages.destroy', [$test, $selectedPassage]) }}">
                @csrf
                @method('DELETE')
                <x-ui.button type="submit" variant="danger">Delete Passage</x-ui.button>
            </form>
        </div>
    </div>
</div>
