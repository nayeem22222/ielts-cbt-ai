@php
    $selectedType = old('question_type', $group->question_type?->value ?? 'form_completion');
    $instructionDefaults = $instructionDefaults ?? [];
@endphp
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
        @if ($errors->has('title'))
            <span class="mt-1.5 block text-xs text-danger-500">{{ $errors->first('title') }}</span>
        @endif
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
        @if ($errors->has('instruction'))
            <span class="mt-1.5 block text-xs text-danger-500">{{ $errors->first('instruction') }}</span>
        @endif
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
            @foreach ($enabledQuestionTypes ?? $questionTypes as $typeOption)
                <option value="{{ $typeOption->value }}" @selected($selectedType === $typeOption->value)>{{ $typeOption->label() }}</option>
            @endforeach
        </select>
        @if ($errors->has('question_type'))
            <span class="mt-1.5 block text-xs text-danger-500">{{ $errors->first('question_type') }}</span>
        @endif
    </div>

    <div>
        <label for="start_question_number" class="block text-sm font-medium text-neutral-700 dark:text-neutral-200">Start Question</label>
        <input
            id="start_question_number"
            name="start_question_number"
            type="number"
            min="1"
            max="40"
            required
            class="mt-1 w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
            x-model.number="groupStart"
            @input="autoGroupTitle()"
            value="{{ old('start_question_number', $group->start_question_number) }}"
        >
        @if ($errors->has('start_question_number'))
            <span class="mt-1.5 block text-xs text-danger-500">{{ $errors->first('start_question_number') }}</span>
        @endif
    </div>

    <div>
        <label for="end_question_number" class="block text-sm font-medium text-neutral-700 dark:text-neutral-200">End Question</label>
        <input
            id="end_question_number"
            name="end_question_number"
            type="number"
            min="1"
            max="40"
            required
            class="mt-1 w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
            x-model.number="groupEnd"
            @input="autoGroupTitle()"
            value="{{ old('end_question_number', $group->end_question_number) }}"
        >
        @if ($errors->has('end_question_number'))
            <span class="mt-1.5 block text-xs text-danger-500">{{ $errors->first('end_question_number') }}</span>
        @endif
    </div>

    <div class="md:col-span-2 rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm dark:border-neutral-800 dark:bg-neutral-900">
        <span class="aa-muted">Section {{ $section->section_number }} allows:</span>
        <span class="font-semibold">Questions {{ $section->start_question_number }}–{{ $section->end_question_number }}</span>
        <span class="aa-muted">· Expected in this group:</span>
        <span class="font-semibold" x-text="groupRangeLabel()"></span>
        <span class="aa-muted">· Questions filled:</span>
        <span class="font-semibold">{{ $group->question_count_label }}</span>
    </div>

    <x-ui.input name="display_order" type="number" min="1" label="Sort Order" :value="old('display_order', $group->display_order)" />

    <div class="flex items-end">
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $group->is_active ?? true))>
            <span>Active</span>
        </label>
    </div>

    <input type="hidden" name="layout_type" value="{{ old('layout_type', $group->layout_type?->value ?? 'form') }}">
</div>

@if ($errors->any())
    <x-ui.alert tone="red" class="mt-4">
        <ul class="list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-ui.alert>
@endif
