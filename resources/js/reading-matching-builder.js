import Sortable from 'sortablejs';

window.Sortable = Sortable;

window.readingMatchingBuilder = (config = {}) => ({
    init() {
        this.initSortable(
            config.optionSortableId ?? 'matching-option-sortable',
            'matching-option-reorder-form',
            'option_ids[]',
            '[data-option-item]',
            '[data-option-drag-handle]',
        );

        this.initSortable(
            config.questionSortableId ?? 'matching-question-sortable',
            'matching-question-reorder-form',
            'question_ids[]',
            '[data-question-item]',
            '[data-question-drag-handle]',
        );
    },

    initSortable(listId, formId, inputName, itemSelector, handleSelector) {
        const list = document.getElementById(listId);
        const form = document.getElementById(formId);

        if (!list || !form) {
            return;
        }

        Sortable.create(list, {
            animation: 150,
            handle: handleSelector,
            draggable: itemSelector,
            onEnd() {
                const attr = itemSelector.includes('option') ? 'data-option-id' : 'data-question-id';
                const ids = [...list.querySelectorAll(itemSelector)].map((item) => item.getAttribute(attr));
                const container = form.querySelector('[data-option-ids], [data-question-ids]');

                if (!container) {
                    return;
                }

                container.innerHTML = '';

                ids.forEach((id) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = inputName;
                    input.value = id;
                    container.appendChild(input);
                });

                form.submit();
            },
        });
    },
});
