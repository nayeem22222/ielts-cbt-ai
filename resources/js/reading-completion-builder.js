import Sortable from 'sortablejs';
import tinymce from 'tinymce/tinymce';

import 'tinymce/icons/default';
import 'tinymce/themes/silver';
import 'tinymce/models/dom';

import 'tinymce/plugins/advlist';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/link';
import 'tinymce/plugins/table';
import 'tinymce/plugins/searchreplace';
import 'tinymce/plugins/wordcount';
import 'tinymce/plugins/fullscreen';

import 'tinymce/skins/ui/oxide/skin.min.css';
import 'tinymce/skins/content/default/content.min.css';

window.Sortable = Sortable;

function compileTableHtml(tableData) {
    const rows = tableData?.rows ?? [];

    if (!rows.length) {
        return '';
    }

    let html = '<table class="completion-table w-full border-collapse"><tbody>';

    rows.forEach((row, rowIndex) => {
        const cells = row.cells ?? [];
        const tag = row.is_header || rowIndex === 0 ? 'th' : 'td';
        html += '<tr>';

        cells.forEach((cell) => {
            let content = cell.content ?? '';

            if (cell.is_blank && cell.blank_number) {
                content = `{{${cell.blank_number}}}`;
            }

            const colspan = Number(cell.colspan ?? 1);
            const rowspan = Number(cell.rowspan ?? 1);
            let attrs = '';

            if (colspan > 1) {
                attrs += ` colspan="${colspan}"`;
            }

            if (rowspan > 1) {
                attrs += ` rowspan="${rowspan}"`;
            }

            html += `<${tag} class="border border-neutral-300 px-3 py-2"${attrs}>${content}</${tag}>`;
        });

        html += '</tr>';
    });

    return `${html}</tbody></table>`;
}

function compileFlowHtml(steps) {
    if (!steps.length) {
        return '';
    }

    let html = '<div class="completion-flow space-y-2">';

    steps.forEach((step, index) => {
        if (index > 0) {
            html += '<div class="text-center text-lg font-bold text-neutral-500">↓</div>';
        }

        let text = step.text ?? '';

        if (step.is_blank && step.blank_number) {
            text = `{{${step.blank_number}}}`;
        }

        html += `<div class="rounded-xl border border-neutral-300 bg-white px-4 py-3 text-center">${text}</div>`;
    });

    return `${html}</div>`;
}

function defaultTableData() {
    return {
        rows: [
            {
                is_header: true,
                cells: [
                    { content: 'Country', is_blank: false, blank_number: 0, colspan: 1, rowspan: 1 },
                    { content: 'Population', is_blank: false, blank_number: 0, colspan: 1, rowspan: 1 },
                ],
            },
            {
                is_header: false,
                cells: [
                    { content: 'France', is_blank: false, blank_number: 0, colspan: 1, rowspan: 1 },
                    { content: '', is_blank: true, blank_number: 0, colspan: 1, rowspan: 1 },
                ],
            },
        ],
    };
}

function defaultFlowSteps() {
    return [
        { text: 'Collect water', is_blank: false, blank_number: 0 },
        { text: '', is_blank: true, blank_number: 0 },
        { text: 'Dry materials', is_blank: false, blank_number: 0 },
    ];
}

function normalizeCompletionTemplateHtml(html) {
    if (!html) {
        return '';
    }

    let normalized = html;

    normalized = normalized.replace(
        /<span[^>]*data-completion-blank=["']?\d+["']?[^>]*>(\{\{[^}]+\}\})<\/span>/gi,
        '$1',
    );

    normalized = normalized.replace(/\{\{((?:[^}]|<[^>]*>)*)\}\}/g, (_, inner) => {
        const cleaned = inner.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim();

        return `{{${cleaned}}}`;
    });

    normalized = normalized.replace(/\[blank:\s*((?:[^\]]|<[^>]*>)*)\]/gi, (_, inner) => {
        const cleaned = inner.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim();

        return `[blank:${cleaned}]`;
    });

    return normalized;
}

function completionPlaceholderHtml(number) {
    const token = `{{${number}}}`;

    return `<span class="completion-blank-token" data-completion-blank="${number}" contenteditable="false" spellcheck="false">${token}</span>`;
}

function ensureEditorSelection(editor) {
    const body = editor.getBody();

    if (!body) {
        return;
    }

    editor.focus();

    const range = editor.selection?.getRng?.();

    if (!range || !body.contains(range.commonAncestorContainer)) {
        editor.selection.select(body, true);
        editor.selection.collapse(false);
    }
}

window.readingCompletionBuilder = (config = {}) => ({
    detectUrl: config.detectUrl ?? '',
    groupStart: Number(config.groupStart ?? 1),
    groupEnd: Number(config.groupEnd ?? 40),
    type: config.type ?? '',
    templateHtml: config.templateHtml ?? '',
    tableData: config.tableData?.rows?.length ? config.tableData : defaultTableData(),
    flowSteps: config.flowSteps?.length ? config.flowSteps : defaultFlowSteps(),
    detectedPlaceholders: [],
    removedCandidates: [],
    liveDetectError: null,
    liveDetectedCount: Number(config.detectedCount ?? 0),
    existingQuestionNumbers: config.existingQuestionNumbers ?? [],
    answerRule: config.answerRule ?? 'one_word_only',
    customAnswerRule: config.customAnswerRule ?? '',
    nextPlaceholderNumber: Number(config.groupStart ?? 1),
    detectTimer: null,

    init() {
        this.refreshDetected();
        this.initEditor();
        this.initSortable();

        if (this.type === 'table_completion') {
            this.templateHtml = compileTableHtml(this.tableData);
            this.refreshDetected();
        }

        if (this.type === 'flow_chart_completion') {
            this.templateHtml = compileFlowHtml(this.flowSteps);
            this.refreshDetected();
        }
    },

    initEditor() {
        const textarea = document.getElementById('completion_template_html')
            ?? document.getElementById('completion_sentence_template_html');

        if (!textarea) {
            return;
        }

        const self = this;
        const isNote = this.type === 'note_completion';

        tinymce.init({
            target: textarea,
            height: 420,
            menubar: 'edit view',
            plugins: 'advlist autolink lists link table searchreplace wordcount fullscreen',
            toolbar: isNote
                ? 'undo redo | styles | bold italic underline | bullist numlist | table | removeformat | searchreplace | fullscreen'
                : 'undo redo | styles | bold italic underline | bullist numlist table | removeformat | searchreplace | fullscreen',
            branding: false,
            promotion: false,
            license_key: 'gpl',
            skin: false,
            content_css: false,
            paste_as_text: false,
            extended_valid_elements: 'span[class|contenteditable|data-completion-blank|spellcheck]',
            content_style: '.completion-blank-token{display:inline-block;background:#ecfdf5;border:1px dashed #2d6a4f;border-radius:4px;padding:0 4px;font-family:ui-monospace,monospace;font-weight:600;color:#1b4332;}',
            setup(editor) {
                const sync = () => {
                    self.templateHtml = normalizeCompletionTemplateHtml(editor.getContent());
                    self.refreshDetected();
                };

                editor.on('init change keyup undo redo SetContent Paste', sync);
            },
        });
    },

    initSortable() {
        const list = document.getElementById('completion-question-sortable');
        const form = document.getElementById('completion-question-reorder-form');

        if (!list || !form) {
            return;
        }

        Sortable.create(list, {
            animation: 150,
            handle: '[data-question-drag-handle]',
            draggable: '[data-question-item]',
            onEnd() {
                const ids = [...list.querySelectorAll('[data-question-item]')].map((item) =>
                    item.getAttribute('data-question-id'),
                );
                const container = form.querySelector('[data-question-ids]');

                if (!container) {
                    return;
                }

                container.innerHTML = '';

                ids.forEach((id) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'question_ids[]';
                    input.value = id;
                    container.appendChild(input);
                });

                form.submit();
            },
        });
    },

    async refreshDetected() {
        clearTimeout(this.detectTimer);

        this.detectTimer = setTimeout(async () => {
            if (!this.detectUrl || !this.templateHtml) {
                this.detectedPlaceholders = [];
                this.removedCandidates = [];
                this.liveDetectError = null;
                this.liveDetectedCount = 0;

                return;
            }

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            const response = await fetch(this.detectUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({ content: this.templateHtml }),
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            this.detectedPlaceholders = data.placeholders ?? [];
            this.removedCandidates = data.removed_candidates ?? [];
            this.liveDetectError = data.valid ? null : (data.error ?? 'Invalid placeholders.');
            this.liveDetectedCount = data.count ?? 0;

            const numbers = this.detectedPlaceholders.map((item) => Number(item.question_number));
            const max = numbers.length ? Math.max(...numbers) : this.groupStart - 1;
            this.nextPlaceholderNumber = Math.min(Math.max(max + 1, this.groupStart), this.groupEnd);
        }, 250);
    },

    insertPlaceholder() {
        if (this.nextPlaceholderNumber > this.groupEnd) {
            return;
        }

        const editor = tinymce.get('completion_template_html') ?? tinymce.get('completion_sentence_template_html');
        const token = `{{${this.nextPlaceholderNumber}}}`;

        if (editor) {
            ensureEditorSelection(editor);

            editor.undoManager.transact(() => {
                editor.insertContent(`${completionPlaceholderHtml(this.nextPlaceholderNumber)}\u00a0`);
            });

            this.templateHtml = normalizeCompletionTemplateHtml(editor.getContent());
        } else {
            this.templateHtml += token;
        }

        this.refreshDetected();
    },

    syncEditorBeforeSubmit() {
        const editor = tinymce.get('completion_template_html') ?? tinymce.get('completion_sentence_template_html');

        if (editor) {
            this.templateHtml = normalizeCompletionTemplateHtml(editor.getContent());
            editor.setContent(this.templateHtml);
            editor.save();
        }
    },

    syncTableBeforeSubmit() {
        this.templateHtml = compileTableHtml(this.tableData);
        this.refreshDetected();
    },

    syncFlowBeforeSubmit() {
        this.templateHtml = compileFlowHtml(this.flowSteps);
        this.refreshDetected();
    },

    addTableRow() {
        const columnCount = this.tableData.rows[0]?.cells?.length ?? 2;
        this.tableData.rows.push({
            is_header: false,
            cells: Array.from({ length: columnCount }, () => ({
                content: '',
                is_blank: false,
                blank_number: 0,
                colspan: 1,
                rowspan: 1,
            })),
        });
        this.syncTableBeforeSubmit();
    },

    deleteTableRow(rowIndex) {
        if (this.tableData.rows.length <= 1) {
            return;
        }

        this.tableData.rows.splice(rowIndex, 1);
        this.syncTableBeforeSubmit();
    },

    addTableColumn() {
        this.tableData.rows.forEach((row) => {
            row.cells.push({
                content: '',
                is_blank: false,
                blank_number: 0,
                colspan: 1,
                rowspan: 1,
            });
        });
        this.syncTableBeforeSubmit();
    },

    deleteTableColumn() {
        if ((this.tableData.rows[0]?.cells?.length ?? 0) <= 1) {
            return;
        }

        this.tableData.rows.forEach((row) => {
            row.cells.pop();
        });
        this.syncTableBeforeSubmit();
    },

    onTableCellBlankToggle(cell) {
        if (cell.is_blank && !cell.blank_number) {
            cell.blank_number = this.nextPlaceholderNumber;
        }

        this.syncTableBeforeSubmit();
    },

    mergeCellRight(rowIndex, cellIndex) {
        const row = this.tableData.rows[rowIndex];
        const cell = row?.cells?.[cellIndex];
        const next = row?.cells?.[cellIndex + 1];

        if (!cell || !next) {
            return;
        }

        cell.colspan = Number(cell.colspan ?? 1) + Number(next.colspan ?? 1);
        row.cells.splice(cellIndex + 1, 1);
        this.syncTableBeforeSubmit();
    },

    setHeaderRow(rowIndex) {
        this.tableData.rows[rowIndex].is_header = true;
        this.syncTableBeforeSubmit();
    },

    addFlowStep() {
        this.flowSteps.push({ text: '', is_blank: false, blank_number: 0 });
        this.syncFlowBeforeSubmit();
    },

    removeFlowStep(index) {
        this.flowSteps.splice(index, 1);
        this.syncFlowBeforeSubmit();
    },

    moveFlowStep(index, direction) {
        const target = index + direction;

        if (target < 0 || target >= this.flowSteps.length) {
            return;
        }

        const [step] = this.flowSteps.splice(index, 1);
        this.flowSteps.splice(target, 0, step);
        this.syncFlowBeforeSubmit();
    },

    onFlowBlankToggle(step) {
        if (step.is_blank && !step.blank_number) {
            step.blank_number = this.nextPlaceholderNumber;
        }

        this.syncFlowBeforeSubmit();
    },
});
