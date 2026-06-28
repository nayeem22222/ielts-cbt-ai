<div class="grid gap-6 xl:grid-cols-2">
    <x-ui.card title="Flow Chart Builder" subtitle="Create steps with optional blank placeholders">
        <form
            method="POST"
            action="{{ route('admin.listening-question-groups.completion-questions.flow-chart', $group) }}"
            class="space-y-4"
            @submit="syncFlowBeforeSubmit($event)"
        >
            @csrf
            <input type="hidden" name="template_html" x-model="templateHtml">
            <input type="hidden" name="flow_steps" :value="JSON.stringify(flowSteps)">

            @if (session('completion_confirm_remove'))
                <x-ui.alert tone="amber">
                    <p>Removing placeholders will delete linked questions. Confirm to continue saving.</p>
                    <input type="hidden" name="confirm_remove" value="1">
                </x-ui.alert>
            @endif

            @include('admin.listening.question-builders.completion.partials.answer-rule-select', [
                'group' => $group,
                'answerRules' => $answerRules,
                'selectedRule' => old('answer_rule', $settings['answer_rule']),
                'customRule' => old('custom_answer_rule', $settings['custom_answer_rule']),
            ])

            <div class="space-y-3">
                <template x-for="(step, index) in flowSteps" :key="'step-'+index">
                    <div>
                        <div class="rounded-xl border border-neutral-300 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-900">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold" x-text="'Step ' + (index + 1)"></p>
                                <div class="flex gap-2">
                                    <button type="button" class="text-xs underline" @click="moveFlowStep(index, -1)" x-show="index > 0">↑</button>
                                    <button type="button" class="text-xs underline" @click="moveFlowStep(index, 1)" x-show="index < flowSteps.length - 1">↓</button>
                                    <button type="button" class="text-xs text-red-600" @click="removeFlowStep(index)">Remove</button>
                                </div>
                            </div>
                            <label class="mb-2 flex items-center gap-2 text-xs">
                                <input type="checkbox" x-model="step.is_blank" @change="onFlowBlankToggle(step)">
                                <span>Blank step</span>
                            </label>
                            <template x-if="step.is_blank">
                                <input type="number" x-model.number="step.blank_number" :min="groupStart" :max="groupEnd" class="w-full rounded border px-2 py-1 text-sm" placeholder="Question number">
                            </template>
                            <template x-if="!step.is_blank">
                                <input type="text" x-model="step.text" class="w-full rounded border px-2 py-1 text-sm" placeholder="Step text">
                            </template>
                        </div>
                        <div class="py-1 text-center text-lg font-bold text-neutral-400" x-show="index < flowSteps.length - 1">↓</div>
                    </div>
                </template>
            </div>

            <x-ui.button type="button" size="sm" variant="outline" @click="addFlowStep()">Add Step</x-ui.button>

            <p class="text-xs aa-muted">Live placeholders: <span class="font-semibold" x-text="detectedPlaceholders.map((item) => item.question_number).join(', ') || '—'"></span></p>

            @if ($errors->any())
                <x-ui.alert tone="red">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-ui.alert>
            @endif

            <x-ui.button type="submit">Save Flow Chart &amp; Sync Questions</x-ui.button>
        </form>
    </x-ui.card>

    <div>
        @include('admin.listening.question-builders.completion.partials.question-panel', [
            'group' => $group,
            'questions' => $questions,
        ])
    </div>
</div>
