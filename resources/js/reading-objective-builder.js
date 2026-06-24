import Sortable from 'sortablejs';

window.Sortable = Sortable;

window.readingObjectiveBuilder = () => ({
    init() {
        const list = document.getElementById('objective-question-sortable');
        const form = document.getElementById('objective-question-reorder-form');

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
});
