import Sortable from 'sortablejs';
import tinymce from 'tinymce/tinymce';

import 'tinymce/icons/default';
import 'tinymce/themes/silver';
import 'tinymce/models/dom';

import 'tinymce/plugins/advlist';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/link';
import 'tinymce/plugins/searchreplace';
import 'tinymce/plugins/wordcount';
import 'tinymce/plugins/fullscreen';

import 'tinymce/skins/ui/oxide/skin.min.css';
import 'tinymce/skins/content/default/content.min.css';

window.Sortable = Sortable;

const LABELS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

function applyParagraphLabels(html) {
    if (!html) {
        return '';
    }

    const template = document.createElement('template');
    template.innerHTML = html.trim();
    const paragraphs = template.content.querySelectorAll('p');

    if (!paragraphs.length) {
        return html;
    }

    let labelIndex = 0;

    paragraphs.forEach((paragraph) => {
        if (paragraph.closest('.reading-passage-paragraph')) {
            return;
        }

        const label = LABELS[labelIndex] ?? String(labelIndex + 1);
        labelIndex += 1;

        const wrapper = document.createElement('div');
        wrapper.className = 'reading-passage-paragraph flex gap-4';

        const labelNode = document.createElement('span');
        labelNode.className = 'reading-passage-label shrink-0 font-bold';
        labelNode.textContent = label;

        const body = document.createElement('div');
        body.className = 'reading-passage-paragraph-body flex-1';
        body.innerHTML = paragraph.innerHTML;

        wrapper.appendChild(labelNode);
        wrapper.appendChild(body);
        paragraph.replaceWith(wrapper);
    });

    const container = document.createElement('div');
    container.appendChild(template.content.cloneNode(true));

    return container.innerHTML;
}

window.readingPassageBuilder = (config = {}) => readingTestBuilder(config);

window.readingTestBuilder = (config = {}) => ({
    previewHtml: config.previewHtml ?? '',
    autoLabels: config.autoLabels ?? true,
    deleteOpen: false,
    groupDeleteOpen: false,
    expandedPassages: config.expandedPassages ?? [],
    groupTitle: config.groupTitle ?? '',
    groupInstruction: config.groupInstruction ?? '',
    groupQuestionType: config.groupQuestionType ?? '',
    groupQuestionTypeLabel: config.groupQuestionTypeLabel ?? '',
    groupStart: config.groupStart ?? '',
    groupEnd: config.groupEnd ?? '',
    instructionDefaults: config.instructionDefaults ?? {},
    questionTypeLabels: config.questionTypeLabels ?? {},

    previewContent() {
        if (!this.autoLabels) {
            return this.previewHtml;
        }

        return applyParagraphLabels(this.previewHtml);
    },

    groupRangeLabel() {
        const start = Number(this.groupStart);
        const end = Number(this.groupEnd);

        if (!start || !end) {
            return '—';
        }

        return start === end ? String(start) : `${start}–${end}`;
    },

    autoGroupTitle() {
        const start = Number(this.groupStart);
        const end = Number(this.groupEnd);

        if (!start || !end) {
            return;
        }

        this.groupTitle = start === end ? `Question ${start}` : `Questions ${start}–${end}`;
    },

    applyTypeInstruction() {
        const suggestion = this.instructionDefaults[this.groupQuestionType] ?? '';
        if (suggestion) {
            this.groupInstruction = suggestion;
        }

        this.groupQuestionTypeLabel = this.questionTypeLabels[this.groupQuestionType] ?? this.groupQuestionType;
    },

    togglePassage(passageId) {
        const id = Number(passageId);

        if (this.isPassageExpanded(id)) {
            this.expandedPassages = this.expandedPassages
                .map(Number)
                .filter((expandedId) => expandedId !== id);
        } else {
            this.expandedPassages = [...this.expandedPassages.map(Number), id];
        }
    },

    isPassageExpanded(passageId) {
        return this.expandedPassages.map(Number).includes(Number(passageId));
    },

    init() {
        this.initEditor();
        this.initPassageSortable(config);
        this.initGroupSortables();
    },

    initEditor() {
        const textarea = document.getElementById(config.editorId ?? 'content_html');

        if (!textarea) {
            return;
        }

        const self = this;

        tinymce.init({
            target: textarea,
            height: 420,
            menubar: 'edit view',
            plugins: 'advlist autolink lists link searchreplace wordcount fullscreen',
            toolbar:
                'undo redo | styles | bold italic underline | bullist numlist | removeformat | searchreplace | fullscreen',
            style_formats: [
                { title: 'Paragraph', block: 'p' },
                { title: 'Heading 2', block: 'h2' },
                { title: 'Heading 3', block: 'h3' },
            ],
            paste_as_text: false,
            branding: false,
            promotion: false,
            license_key: 'gpl',
            skin: false,
            content_css: false,
            content_style:
                'body{font-family:Georgia,serif;font-size:15px;line-height:1.8;color:#171717;padding:12px;} p{margin:0 0 1rem;}',
            setup(editor) {
                const sync = () => {
                    self.previewHtml = editor.getContent();
                };

                editor.on('init change keyup undo redo SetContent Paste', sync);
            },
        });
    },

    initPassageSortable(config) {
        const list = document.getElementById(config.sortableId ?? 'passage-sortable-list');
        const form = document.getElementById(config.reorderFormId ?? 'passage-reorder-form');

        if (!list || !form) {
            return;
        }

        Sortable.create(list, {
            animation: 150,
            handle: '[data-passage-drag-handle]',
            draggable: '[data-passage-item]',
            onEnd() {
                const ids = [...list.querySelectorAll('[data-passage-item]')].map((item) =>
                    item.getAttribute('data-passage-id'),
                );
                const container = form.querySelector('[data-passage-ids]');

                if (!container) {
                    return;
                }

                container.innerHTML = '';

                ids.forEach((id) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'passage_ids[]';
                    input.value = id;
                    container.appendChild(input);
                });

                form.submit();
            },
        });
    },

    initGroupSortables() {
        document.querySelectorAll('[data-group-sortable-list]').forEach((list) => {
            const passageId = list.getAttribute('data-group-sortable-list');
            const form = document.getElementById(`group-reorder-form-${passageId}`);

            if (!form) {
                return;
            }

            Sortable.create(list, {
                animation: 150,
                handle: '[data-group-drag-handle]',
                draggable: '[data-group-item]',
                onEnd() {
                    const ids = [...list.querySelectorAll('[data-group-item]')].map((item) =>
                        item.getAttribute('data-group-id'),
                    );
                    const container = form.querySelector('[data-group-ids]');

                    if (!container) {
                        return;
                    }

                    container.innerHTML = '';

                    ids.forEach((id) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'group_ids[]';
                        input.value = id;
                        container.appendChild(input);
                    });

                    form.submit();
                },
            });
        });
    },

    syncEditorBeforeSubmit() {
        const editor = tinymce.get('content_html');

        if (editor) {
            editor.save();
        }
    },
});
