export function getActiveQuestionNumber(state) {
    return Number(
        state.activeQuestionNumber
        ?? state.currentQuestion
        ?? state.current_question_number
        ?? 1,
    );
}

export function assignActiveQuestionNumber(state, number) {
    const resolved = Number(number);

    if (!resolved) {
        return 0;
    }

    state.activeQuestionNumber = resolved;
    state.currentQuestion = resolved;

    return resolved;
}

export function resolveQuestionNumberFromTarget(target) {
    if (!(target instanceof Element)) {
        return 0;
    }

    const input = target.closest('.listening-answer-input');

    if (input?.dataset.questionNumber) {
        return Number(input.dataset.questionNumber);
    }

    const host = target.closest(
        '[data-question-number].listening-question-card, '
        + '[data-question-number].listening-matching-row, '
        + '[data-question-number].listening-dnd-dropzone, '
        + '[data-question-number].listening-blank, '
        + '[data-question-number].listening-inline-field, '
        + '[data-question-number].listening-short-answer-item',
    );

    if (host?.dataset.questionNumber) {
        return Number(host.dataset.questionNumber);
    }

    const fallback = target.closest('[data-question-number]');

    return Number(fallback?.dataset.questionNumber ?? 0);
}

export function bindActiveQuestionInteractions(root, activate) {
    if (!root) {
        return;
    }

    const syncFromEvent = (event) => {
        const number = resolveQuestionNumberFromTarget(event.target);

        if (number > 0) {
            activate(number);
        }
    };

    root.addEventListener('focusin', syncFromEvent);
    root.addEventListener('click', syncFromEvent);
    root.addEventListener('change', (event) => {
        if (event.target.closest('.listening-answer-input')) {
            syncFromEvent(event);
        }
    });
}
