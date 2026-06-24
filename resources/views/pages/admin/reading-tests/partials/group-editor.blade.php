{{-- Variables: $test, $passage, $group, $questionTypes, $groupStatuses --}}

<x-ui.card title="Question Group Editor" :subtitle="$group->title">
    <form
        method="POST"
        action="{{ route('admin.reading-tests.passages.groups.update', [$test, $passage, $group]) }}"
        class="space-y-5"
    >
        @csrf
        @method('PUT')

        <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label for="group_title" class="block text-sm font-medium text-neutral-700 dark:text-neutral-200">Group Title</label>
                <input
                    id="group_title"
                    name="title"
                    type="text"
                    required
                    class="mt-1 w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                    x-model="groupTitle"
                    value="{{ old('title', $group->title) }}"
                >
                <p class="mt-1 text-xs aa-muted">Example: Questions 1–4</p>
            </div>

            <div class="md:col-span-2">
                <label for="group_instruction" class="block text-sm font-medium text-neutral-700 dark:text-neutral-200">Instruction</label>
                <textarea
                    id="group_instruction"
                    name="instruction"
                    rows="4"
                    class="mt-1 w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                    x-model="groupInstruction"
                >{{ old('instruction', $group->instruction) }}</textarea>
            </div>

            <div class="md:col-span-2">
                <label for="question_type" class="block text-sm font-medium text-neutral-700 dark:text-neutral-200">Question Type</label>
                <select
                    id="question_type"
                    name="question_type"
                    required
                    class="mt-1 w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                    x-model="groupQuestionType"
                    @change="applyTypeInstruction()"
                >
                    @foreach ($questionTypes as $type)
                        <option value="{{ $type->value }}" @selected(old('question_type', $group->question_type?->value) === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="start_question" class="block text-sm font-medium text-neutral-700 dark:text-neutral-200">Start Question</label>
                <input
                    id="start_question"
                    name="start_question"
                    type="number"
                    min="1"
                    required
                    class="mt-1 w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                    x-model.number="groupStart"
                    @input="autoGroupTitle()"
                    value="{{ old('start_question', $group->start_question) }}"
                >
            </div>

            <div>
                <label for="end_question" class="block text-sm font-medium text-neutral-700 dark:text-neutral-200">End Question</label>
                <input
                    id="end_question"
                    name="end_question"
                    type="number"
                    min="1"
                    required
                    class="mt-1 w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                    x-model.number="groupEnd"
                    @input="autoGroupTitle()"
                    value="{{ old('end_question', $group->end_question) }}"
                >
            </div>

            <div class="md:col-span-2 rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm dark:border-neutral-800 dark:bg-neutral-900">
                <span class="aa-muted">Passage {{ $passage->part_number }} allows:</span>
                <span class="font-semibold">Questions {{ $passage->start_question }}–{{ $passage->end_question }}</span>
                <span class="aa-muted">· Expected in this group:</span>
                <span class="font-semibold" x-text="groupRangeLabel()"></span>
                <span class="aa-muted">· Questions filled:</span>
                <span class="font-semibold">{{ $group->question_count_label }}</span>
            </div>

            <x-ui.input name="sort_order" type="number" min="1" label="Sort Order" :value="old('sort_order', $group->sort_order)" />

            <x-ui.select name="status" label="Status">
                @foreach ($groupStatuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $group->status?->value ?? 'draft') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </x-ui.select>
        </div>

        @if (isset($errors) && $errors->any())
            <x-ui.alert tone="red">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <div class="flex flex-wrap gap-2">
            <x-ui.button type="submit">Save Question Group</x-ui.button>
            @if ($group->question_type?->isMatchingBuilderType())
                <x-ui.button href="{{ route('admin.reading-question-groups.questions.index', $group) }}" variant="secondary">Manage Questions</x-ui.button>
            @elseif ($group->question_type?->isObjectiveBuilderType())
                <x-ui.button href="{{ route('admin.reading-question-groups.objective-questions.index', $group) }}" variant="secondary">Manage Questions</x-ui.button>
            @endif
            <x-ui.button type="button" variant="outline" @click="groupDeleteOpen = true">Delete</x-ui.button>
        </div>
    </form>
</x-ui.card>

<x-ui.card title="Group Preview">
    <div class="rounded-3xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-950">
        <div class="mx-auto max-w-3xl space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-950 dark:text-blue-200" x-text="groupQuestionTypeLabel || 'Question Type'"></span>
                <span class="inline-flex items-center rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs font-medium text-neutral-700 dark:bg-neutral-800 dark:text-neutral-200">
                    Questions <span x-text="groupRangeLabel()"></span>
                </span>
            </div>
            <h3 class="text-xl font-bold text-neutral-900 dark:text-white" x-text="groupTitle || 'Question Group Title'"></h3>
            <p class="text-sm italic leading-7 text-neutral-600 dark:text-neutral-300" x-text="groupInstruction || 'Group instruction will appear here.'"></p>
            <p class="text-xs aa-muted">Individual questions are not shown in Volume 4A preview.</p>
        </div>
    </div>
</x-ui.card>

<div x-show="groupDeleteOpen" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-neutral-950/50 p-4">
    <div @click.outside="groupDeleteOpen = false" class="aa-card w-full max-w-lg p-6">
        <h3 class="text-lg font-semibold text-neutral-900 dark:text-white">Delete Question Group?</h3>
        <dl class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between gap-4">
                <dt class="aa-muted">Question Type</dt>
                <dd class="font-medium">{{ $group->question_type?->label() }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="aa-muted">Question Range</dt>
                <dd class="font-medium">{{ $group->question_range_label }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="aa-muted">Question Count</dt>
                <dd class="font-medium">{{ $group->expected_questions_count }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="aa-muted">Questions Created</dt>
                <dd class="font-medium">{{ $group->questions_count }} / {{ $group->expected_questions_count }}</dd>
            </div>
        </dl>
        <p class="mt-4 text-sm text-red-600 dark:text-red-400">
            Deleting this group will also remove all linked questions.
        </p>
        <div class="mt-6 flex justify-end gap-2">
            <x-ui.button type="button" variant="outline" @click="groupDeleteOpen = false">Cancel</x-ui.button>
            <form method="POST" action="{{ route('admin.reading-tests.passages.groups.destroy', [$test, $passage, $group]) }}">
                @csrf
                @method('DELETE')
                <x-ui.button type="submit" variant="danger">Delete Group</x-ui.button>
            </form>
        </div>
    </div>
</div>
