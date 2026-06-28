import Sortable from 'sortablejs';

window.Sortable = Sortable;

window.listeningTestBuilder = (config = {}) => ({
    groupDeleteOpen: false,
    expandedSections: config.expandedSections ?? [],
    groupTitle: config.groupTitle ?? '',
    groupInstruction: config.groupInstruction ?? '',
    groupQuestionType: config.groupQuestionType ?? '',
    groupQuestionTypeLabel: config.groupQuestionTypeLabel ?? '',
    groupStart: config.groupStart ?? '',
    groupEnd: config.groupEnd ?? '',
    instructionDefaults: config.instructionDefaults ?? {},
    questionTypeLabels: config.questionTypeLabels ?? {},

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

    toggleSection(sectionId) {
        const id = Number(sectionId);

        if (this.isSectionExpanded(id)) {
            this.expandedSections = this.expandedSections
                .map(Number)
                .filter((expandedId) => expandedId !== id);
        } else {
            this.expandedSections = [...this.expandedSections.map(Number), id];
        }
    },

    isSectionExpanded(sectionId) {
        return this.expandedSections.map(Number).includes(Number(sectionId));
    },

    init() {
        this.initGroupSortables();
    },

    initGroupSortables() {
        document.querySelectorAll('[data-group-sortable-list]').forEach((list) => {
            const sectionId = list.getAttribute('data-group-sortable-list');
            const form = document.getElementById(`group-reorder-form-${sectionId}`);

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
});
