<div class="grid gap-6 xl:grid-cols-2">
    <x-ui.card title="Content Template" subtitle="Insert placeholders like @{{27}} or [Blank:27]">
        <form
            method="POST"
            action="{{ route('admin.reading-question-groups.completion-questions.template', $group) }}"
            class="space-y-4"
            @submit="syncEditorBeforeSubmit($event)"
        >
            @csrf

            @if (session('completion_confirm_remove'))
                <x-ui.alert tone="amber">
                    <p>Removing placeholders will delete linked questions. Confirm to continue saving.</p>
                    <input type="hidden" name="confirm_remove" value="1">
                </x-ui.alert>
            @endif

            @include('pages.admin.reading-tests.completion.partials.answer-rule-select', [
                'group' => $group,
                'answerRules' => $answerRules,
                'selectedRule' => old('answer_rule', $settings['answer_rule']),
                'customRule' => old('custom_answer_rule', $settings['custom_answer_rule']),
            ])

            <div>
                <label for="{{ $editorId }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-200">{{ $editorLabel }}</label>
                <textarea
                    id="{{ $editorId }}"
                    name="template_html"
                    rows="14"
                    class="mt-1 w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm font-mono dark:border-neutral-700 dark:bg-neutral-900"
                >{{ old('template_html', $settings['template_html']) }}</textarea>
                <p class="mt-2 text-xs aa-muted">Detected blanks: <span class="font-semibold" x-text="detectedPlaceholders.join(', ') || '—'"></span></p>
            </div>

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
                <x-ui.button type="submit">Save Template &amp; Sync Questions</x-ui.button>
                <x-ui.button type="button" variant="outline" @click="insertPlaceholder()">Insert Next Blank</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <div>
        @include('pages.admin.reading-tests.completion.partials.question-panel', [
            'group' => $group,
            'questions' => $questions ?? collect(),
        ])
    </div>
</div>
