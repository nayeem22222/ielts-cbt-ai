import {
    assignActiveQuestionNumber,
    bindActiveQuestionInteractions,
    getActiveQuestionNumber,
} from './active-question';

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

export function createNavigation(state, ui, autosave, palette) {
    let navigating = false;

    const maxQuestion = () => {
        const numbers = (state.questions ?? []).map((q) => Number(q.question_number)).filter((n) => n > 0);
        if (numbers.length === 0) {
            return 40;
        }

        return Math.max(...numbers);
    };

    const sectionForQuestion = (number) => {
        const section = state.sections?.find(
            (item) => number >= item.start_question_number && number <= item.end_question_number,
        );

        return section?.number ?? (number <= 10 ? 1 : number <= 20 ? 2 : number <= 30 ? 3 : 4);
    };

    const showSection = (sectionNumber) => {
        state.currentSection = sectionNumber;

        document.querySelectorAll('.listening-section').forEach((el) => {
            el.classList.toggle('hidden', Number(el.dataset.section) !== sectionNumber);
        });

        palette.updatePartBoxes(sectionNumber);
    };

    const setActiveQuestionVisual = (number) => {
        document.querySelectorAll('.listening-target-highlight').forEach((el) => {
            el.classList.remove('listening-target-highlight');
        });

        document.querySelectorAll(
            '.listening-question-card.is-focused, .listening-matching-row.is-focused, .listening-blank.is-focused, .listening-inline-field.is-focused',
        ).forEach((el) => {
            el.classList.remove('is-focused');
        });

        if (!number) {
            return;
        }

        const target =
            document.querySelector(`[data-question-number="${number}"].listening-question-card`)
            ?? document.querySelector(`[data-question-number="${number}"].listening-matching-question-row`)
            ?? document.querySelector(`[data-question-number="${number}"].listening-short-answer-item`)
            ?? document.querySelector(`.listening-blank[data-question-number="${number}"]`)
            ?? document.querySelector(`.listening-inline-field[data-question-number="${number}"]`)
            ?? document.querySelector(`[data-question-number="${number}"]`);

        if (!target) {
            return;
        }

        target.classList.add('listening-target-highlight', 'is-focused');
    };

    const setActiveQuestionNumber = (number) => {
        const resolved = assignActiveQuestionNumber(state, number);

        if (!resolved) {
            return;
        }

        palette.setCurrent(resolved);
        setActiveQuestionVisual(resolved);
    };

    const highlightQuestion = (number) => {
        setActiveQuestionNumber(number);

        const target =
            document.querySelector(`[data-question-number="${number}"] .listening-blank-input`)
            ?? document.querySelector(`.listening-blank[data-question-number="${number}"]`)
            ?? document.querySelector(`[data-question-number="${number}"].listening-question-card`)
            ?? document.querySelector(`[data-question-number="${number}"].listening-matching-question-row`)
            ?? document.querySelector(`[data-question-number="${number}"]`)
            ?? document.querySelector(`input[data-question-number="${number}"]`)?.closest(
                '[data-question-number], .listening-question-card, .listening-group-shell, .listening-matching-row, .listening-short-answer-item, .listening-blank, .listening-inline-field',
            );

        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    };

    const persistPosition = async (direction = 'jump') => {
        try {
            await fetch(state.routes.navigation_update, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify({
                    current_section_number: state.currentSection,
                    current_question_number: getActiveQuestionNumber(state),
                    direction,
                }),
            });
        } catch {
            ui.setSaveStatus('Offline');
        }
    };

    const showQuestion = async (number, direction = 'jump') => {
        if (navigating) return;
        navigating = true;

        await autosave.flushBeforeNavigation();

        const resolved = assignActiveQuestionNumber(state, number);
        const sectionNumber = sectionForQuestion(resolved);
        showSection(sectionNumber);
        palette.setCurrent(resolved);
        highlightQuestion(resolved);
        await persistPosition(direction);

        navigating = false;
    };

    const showSectionOnly = async (sectionNumber, direction = 'section_switch') => {
        if (navigating) return;
        navigating = true;

        await autosave.flushBeforeNavigation();

        const section = state.sections?.find((item) => item.number === sectionNumber);
        const firstQuestion = section?.start_question_number ?? [1, 11, 21, 31][sectionNumber - 1] ?? 1;

        state.currentSection = sectionNumber;
        assignActiveQuestionNumber(state, firstQuestion);
        showSection(sectionNumber);
        palette.setCurrent(firstQuestion);
        highlightQuestion(firstQuestion);
        await persistPosition(direction);

        navigating = false;
    };

    const bind = () => {
        bindActiveQuestionInteractions(
            document.getElementById('listening-question-area'),
            setActiveQuestionNumber,
        );

        document.getElementById('listening-prev')?.addEventListener('click', () => {
            const prevSection = Math.max(1, state.currentSection - 1);
            if (prevSection !== state.currentSection) {
                showSectionOnly(prevSection, 'previous');
                return;
            }

            showQuestion(Math.max(1, getActiveQuestionNumber(state) - 1), 'previous');
        });

        document.getElementById('listening-next')?.addEventListener('click', () => {
            const nextSection = Math.min(4, state.currentSection + 1);
            const sectionEnd = state.sections?.find((item) => item.number === state.currentSection)?.end_question_number ?? maxQuestion();
            const activeQuestion = getActiveQuestionNumber(state);

            if (activeQuestion >= sectionEnd && nextSection !== state.currentSection) {
                showSectionOnly(nextSection, 'next');
                return;
            }

            showQuestion(Math.min(maxQuestion(), activeQuestion + 1), 'next');
        });

        document.querySelectorAll('.listening-palette-item').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.stopPropagation();
                showQuestion(Number(btn.dataset.questionNumber), 'jump');
            });
            btn.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    showQuestion(Number(btn.dataset.questionNumber), 'jump');
                }
            });
        });

        document.querySelectorAll('.listening-part-tab').forEach((box) => {
            const activate = () => showSectionOnly(Number(box.dataset.section), 'section_switch');
            box.addEventListener('click', activate);
            box.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    activate();
                }
            });
        });

        document.getElementById('listening-flag-current')?.addEventListener('click', async () => {
            const activeQuestion = getActiveQuestionNumber(state);
            const question = state.questions?.find((q) => q.question_number === activeQuestion);
            if (!question) return;

            const url = state.routes.flag.replace('__QUESTION__', question.id);
            const flagged = !question.is_flagged;

            await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify({ flagged }),
            }).then((res) => res.json()).then((data) => {
                question.is_flagged = flagged;
                if (data.palette) palette.update(data.palette);
            });
        });

        document.getElementById('listening-clear-current')?.addEventListener('click', () => {
            const activeQuestion = getActiveQuestionNumber(state);
            const question = state.questions?.find((q) => q.question_number === activeQuestion);
            if (!question) return;

            document.querySelectorAll(`[data-question-id="${question.id}"]`).forEach((input) => {
                if (input.type === 'checkbox' || input.type === 'radio') {
                    input.checked = false;
                } else {
                    input.value = '';
                }
            });

            autosave.queueSave(question.id, null);
        });
    };

    return {
        showQuestion,
        showSection,
        showSectionOnly,
        bind,
        sectionForQuestion,
        getActiveQuestionNumber: () => getActiveQuestionNumber(state),
        setActiveQuestionNumber,
        syncQuestionSelector: setActiveQuestionNumber,
    };
}
