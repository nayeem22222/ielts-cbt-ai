export function applyMultipleAnswerLimits(root) {
    if (!(root instanceof Element)) {
        return;
    }

    const limit = Number(root.dataset.requiredAnswers ?? 0);

    if (limit <= 0) {
        return;
    }

    const boxes = root.querySelectorAll('input.listening-multiple-answer-checkbox');
    const checkedCount = [...boxes].filter((box) => box.checked).length;

    boxes.forEach((box) => {
        box.disabled = !box.checked && checkedCount >= limit;
    });
}

export function bindMultipleAnswerLimits(root) {
    if (!(root instanceof Element)) {
        return;
    }

    root.querySelectorAll('[data-required-answers]').forEach((container) => {
        applyMultipleAnswerLimits(container);
    });

    root.addEventListener('change', (event) => {
        const input = event.target.closest('.listening-multiple-answer-checkbox');

        if (!input) {
            return;
        }

        const container = input.closest('[data-required-answers]');

        if (!container) {
            return;
        }

        const limit = Number(container.dataset.requiredAnswers ?? 0);

        if (limit > 0) {
            const boxes = container.querySelectorAll('input.listening-multiple-answer-checkbox');
            const checked = [...boxes].filter((box) => box.checked);

            if (checked.length > limit) {
                input.checked = false;
            }
        }

        applyMultipleAnswerLimits(container);
    });
}
