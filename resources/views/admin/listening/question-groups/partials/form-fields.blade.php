@php
    $selectedType = old('question_type', $group->question_type?->value ?? 'form_completion');
    $typeLayouts = collect($questionTypeSchemas ?? [])->pluck('default_layout', 'type')->all();
    $settings = old('settings', $group->settings ?? ['word_limit' => 2, 'template_type' => 'form']);
    if (is_string($settings)) {
        $settings = json_decode($settings, true) ?: [];
    }
    $instructionDefaults = $instructionDefaults ?? [];
@endphp
<div
    class="space-y-6"
    x-data="{
        start: {{ (int) old('start_question_number', $group->start_question_number ?? $section->start_question_number) }},
        end: {{ (int) old('end_question_number', $group->end_question_number ?? $section->start_question_number) }},
        type: @js($selectedType),
        groupTitle: @js(old('title', $group->title ?? '')),
        groupInstruction: @js(old('instruction', $group->instruction ?? '')),
        content: @js(old('content', $group->content ?? '')),
        settings: @js($settings),
        typeLayouts: @js($typeLayouts),
        instructionDefaults: @js($instructionDefaults),
        completionTypes: @js(['form_completion', 'note_completion', 'sentence_completion', 'summary_completion']),
        applyTypeDefaults() {
            const layout = this.typeLayouts[this.type];
            if (layout) {
                const el = document.getElementById('layout_type');
                if (el) {
                    el.value = layout;
                }
            }
            const instruction = this.instructionDefaults[this.type];
            if (instruction) {
                this.groupInstruction = instruction;
            }
        },
        autoGroupTitle() {
            if (this.start === this.end) {
                this.groupTitle = `Question ${this.start}`;
            } else if (this.start < this.end) {
                this.groupTitle = `Questions ${this.start}–${this.end}`;
            }
        },
        expectedQuestions() {
            return Math.max(0, (this.end - this.start) + 1);
        },
        blankNumbersInContent() {
            const pattern = /\[blank:(\d+)\]/g;
            const nums = new Set();
            let match;
            while ((match = pattern.exec(this.content)) !== null) {
                nums.add(parseInt(match[1], 10));
            }
            return [...nums].sort((a, b) => a - b);
        },
        blankCount() {
            return this.blankNumbersInContent().length;
        },
        blanksMatchRange() {
            if (! this.completionTypes.includes(this.type)) {
                return true;
            }
            const expected = [];
            for (let i = this.start; i <= this.end; i++) {
                expected.push(i);
            }
            const found = this.blankNumbersInContent();
            if (found.length !== expected.length) {
                return false;
            }
            return expected.every((num, index) => found[index] === num);
        },
        generateBlanksForRange() {
            const lines = [];
            for (let i = this.start; i <= this.end; i++) {
                lines.push(`[blank:${i}]`);
            }
            this.content = lines.join('\n');
        },
        init() {
            @if (! $group->exists && empty(old('title')))
                this.autoGroupTitle();
            @endif
            @if (! $group->exists)
                this.$nextTick(() => this.applyTypeDefaults());
            @endif
        },
    }"
>
    <x-ui.card title="Essential details">
        <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-ui.select name="question_type" label="Question Type" required x-model="type" @change="applyTypeDefaults(); autoGroupTitle()" :error="$errors->first('question_type')">
                    @foreach ($enabledQuestionTypes ?? $questionTypes as $typeOption)
                        <option value="{{ $typeOption->value }}" @selected($selectedType === $typeOption->value)>{{ $typeOption->label() }}</option>
                    @endforeach
                </x-ui.select>
                <div class="mt-2 text-xs aa-muted">
                    @foreach (collect($questionTypeSchemas ?? []) as $schema)
                        <p x-show="type === @js($schema['type'])" x-cloak>
                            Layout: {{ $schema['default_layout'] }} · Answer format: {{ $schema['default_answer_format'] }}
                        </p>
                    @endforeach
                </div>
            </div>

            <div class="md:col-span-2 rounded-xl border border-blue-100 bg-blue-50/60 p-3 text-sm dark:border-blue-900/40 dark:bg-blue-950/20">
                <p>
                    <span class="font-medium">Section {{ $section->section_number }}</span>
                    · Official range Q{{ $section->start_question_number }}–Q{{ $section->end_question_number }}
                </p>
                @if (! empty($availableRanges))
                    <p class="mt-1 aa-muted">
                        Suggested next range:
                        @foreach ($availableRanges as $range)
                            Q{{ $range['start'] }}–Q{{ $range['end'] }}{{ ! $loop->last ? ',' : '' }}
                        @endforeach
                    </p>
                @else
                    <p class="mt-1 text-amber-700 dark:text-amber-300">This section has no free question numbers left.</p>
                @endif
            </div>

            <x-ui.input
                name="start_question_number"
                type="number"
                min="1"
                max="40"
                label="Start Question"
                x-model.number="start"
                @input="autoGroupTitle()"
                :value="old('start_question_number', $group->start_question_number)"
                :error="$errors->first('start_question_number')"
                required
            />
            <x-ui.input
                name="end_question_number"
                type="number"
                min="1"
                max="40"
                label="End Question"
                x-model.number="end"
                @input="autoGroupTitle()"
                :value="old('end_question_number', $group->end_question_number)"
                :error="$errors->first('end_question_number')"
                required
            />
            <div class="md:col-span-2 text-sm aa-muted">
                Questions in this group: <span class="font-semibold" x-text="expectedQuestions()"></span>
            </div>

            <div class="md:col-span-2">
                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-neutral-800 dark:text-neutral-200">Title</span>
                    <input
                        type="text"
                        name="title"
                        class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm dark:border-neutral-800 dark:bg-neutral-900"
                        x-model="groupTitle"
                        placeholder="e.g. Questions 1–4"
                    >
                </label>
                @if ($errors->has('title'))
                    <span class="mt-1.5 block text-xs text-danger-500">{{ $errors->first('title') }}</span>
                @endif
            </div>

            <div class="md:col-span-2">
                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-neutral-800 dark:text-neutral-200">Group Instruction</span>
                    <textarea
                        name="instruction"
                        rows="3"
                        class="min-h-24 w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm dark:border-neutral-800 dark:bg-neutral-900"
                        x-model="groupInstruction"
                        placeholder="Instructions shown to students above this question group."
                    ></textarea>
                </label>
                @if ($errors->has('instruction'))
                    <span class="mt-1.5 block text-xs text-danger-500">{{ $errors->first('instruction') }}</span>
                @endif
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="Type-specific content">
        <div class="grid gap-4 md:grid-cols-2">
            @include('admin.listening.question-types.partials.type-specific-form', ['selected' => $selectedType])
        </div>
    </x-ui.card>

    <details class="rounded-2xl border border-neutral-200 p-4 dark:border-neutral-800" @if ($errors->hasAny(['layout_type', 'audio_id', 'image_path', 'transcript_reference'])) open @endif>
        <summary class="cursor-pointer text-sm font-semibold">Advanced options (optional)</summary>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <x-ui.select name="layout_type" id="layout_type" label="Layout Type" required :error="$errors->first('layout_type')">
                    @foreach ($layoutTypes as $layout)
                        <option value="{{ $layout->value }}" @selected(old('layout_type', $group->layout_type?->value ?? 'default') === $layout->value)>{{ $layout->label() }}</option>
                    @endforeach
                </x-ui.select>
                <p class="mt-1 text-xs aa-muted">Usually set automatically from question type.</p>
            </div>

            <x-ui.select name="audio_id" label="Audio Reference" :error="$errors->first('audio_id')">
                <option value="">Use section audio</option>
                @foreach ($audios ?? [] as $audio)
                    <option value="{{ $audio->id }}" @selected((string) old('audio_id', $group->audio_id ?? '') === (string) $audio->id)>{{ $audio->original_name }}</option>
                @endforeach
            </x-ui.select>

            <div class="md:col-span-2" x-show="['map_labelling', 'plan_labelling', 'diagram_labelling'].includes(type)" x-cloak>
                <x-ui.input name="image_path" label="Image / Map Path" :value="old('image_path', $group->image_path)" :error="$errors->first('image_path')" />
                <x-ui.input name="image_alt" label="Image Alt Text" :value="old('image_alt', $group->image_alt)" class="mt-3" :error="$errors->first('image_alt')" />
            </div>

            @include('admin.listening.question-groups.partials.transcript-reference')

            <label class="flex items-center gap-2 text-sm md:col-span-2">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $group->is_active ?? true))>
                <span>Active</span>
            </label>
        </div>
    </details>

    @if ($errors->any())
        <x-ui.alert tone="red" title="Please fix the following:">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif
</div>
