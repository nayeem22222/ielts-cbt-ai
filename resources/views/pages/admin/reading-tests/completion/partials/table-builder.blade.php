<div class="grid gap-6 xl:grid-cols-2">
    <x-ui.card title="Table Builder" subtitle="Each cell can be text or a blank placeholder">
        <form
            method="POST"
            action="{{ route('admin.reading-question-groups.completion-questions.table', $group) }}"
            class="space-y-4"
            @submit="syncTableBeforeSubmit($event)"
        >
            @csrf
            <input type="hidden" name="template_html" x-model="templateHtml">
            <input type="hidden" name="table_data" :value="JSON.stringify(tableData)">

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

            <div class="flex flex-wrap gap-2">
                <x-ui.button type="button" size="sm" variant="outline" @click="addTableRow()">Add Row</x-ui.button>
                <x-ui.button type="button" size="sm" variant="outline" @click="deleteTableRow(tableData.rows.length - 1)">Delete Row</x-ui.button>
                <x-ui.button type="button" size="sm" variant="outline" @click="addTableColumn()">Add Column</x-ui.button>
                <x-ui.button type="button" size="sm" variant="outline" @click="deleteTableColumn()">Delete Column</x-ui.button>
            </div>

            <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700">
                <table class="min-w-full border-collapse text-sm">
                    <template x-for="(row, rowIndex) in tableData.rows" :key="'row-'+rowIndex">
                        <tbody>
                            <tr>
                                <template x-for="(cell, cellIndex) in row.cells" :key="'cell-'+rowIndex+'-'+cellIndex">
                                    <td class="border border-neutral-200 p-2 align-top dark:border-neutral-700">
                                        <div class="space-y-2">
                                            <label class="flex items-center gap-2 text-xs">
                                                <input type="checkbox" x-model="cell.is_blank" @change="onTableCellBlankToggle(cell)">
                                                <span>Blank</span>
                                            </label>
                                            <template x-if="cell.is_blank">
                                                <input type="number" x-model.number="cell.blank_number" :min="groupStart" :max="groupEnd" class="w-full rounded border px-2 py-1 text-sm" placeholder="Q#">
                                            </template>
                                            <template x-if="!cell.is_blank">
                                                <input type="text" x-model="cell.content" class="w-full rounded border px-2 py-1 text-sm" placeholder="Cell text">
                                            </template>
                                            <div class="flex gap-1">
                                                <button type="button" class="text-xs underline" @click="mergeCellRight(rowIndex, cellIndex)">Merge →</button>
                                                <button type="button" class="text-xs underline" @click="setHeaderRow(rowIndex)">Header</button>
                                            </div>
                                        </div>
                                    </td>
                                </template>
                            </tr>
                        </tbody>
                    </template>
                </table>
            </div>

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

            <x-ui.button type="submit">Save Table &amp; Sync Questions</x-ui.button>
        </form>
    </x-ui.card>

    <div>
        @include('pages.admin.reading-tests.completion.partials.question-panel', [
            'group' => $group,
            'questions' => $questions,
        ])
    </div>
</div>
