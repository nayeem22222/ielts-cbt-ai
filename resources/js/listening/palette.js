const statusClasses = {
    current: 'is-current',
    answered: 'is-answered',
    flagged: 'is-flagged',
    unanswered: '',
};

export function createPalette(state) {
    const applyClasses = (btn, status, isCurrent) => {
        btn.dataset.status = isCurrent ? 'current' : status;
        btn.classList.remove('is-current', 'is-answered', 'is-flagged');
        const resolved = isCurrent ? 'current' : status;
        const cls = statusClasses[resolved] ?? statusClasses.unanswered;
        if (cls) btn.classList.add(cls);
    };

    const resolvePaletteStatus = (item) => {
        if (item.is_flagged || item.status === 'flagged') {
            return 'flagged';
        }

        if (item.is_answered || item.status === 'answered') {
            return 'answered';
        }

        return 'unanswered';
    };

    const currentQuestionNumber = () => (
        state.activeQuestionNumber
        ?? state.currentQuestion
        ?? state.current_question_number
        ?? 1
    );

    const updatePartBoxes = (activeSection) => {
        document.querySelectorAll('.listening-part-tab').forEach((box) => {
            const section = Number(box.dataset.section);
            const isActive = section === activeSection;
            box.classList.toggle('is-expanded', isActive);
            const grid = box.querySelector('.listening-part-q-grid');
            if (grid) grid.hidden = !isActive;
        });
    };

    const updatePartSummaries = (items) => {
        const bySection = {};
        items.forEach((item) => {
            const section = Number(item.section_number ?? 1);
            if (!bySection[section]) bySection[section] = { answered: 0, total: 0 };
            bySection[section].total += 1;
            if (item.is_answered || item.status === 'answered') bySection[section].answered += 1;
        });

        Object.entries(bySection).forEach(([section, counts]) => {
            const el = document.querySelector(`.listening-part-answered-count[data-part="${section}"]`);
            if (el) el.textContent = String(counts.answered);
        });
    };

    const updateBlankBadges = (items, currentQuestion) => {
        const byNumber = new Map(items.map((item) => [item.question_number ?? item.number, item]));

        document.querySelectorAll('.listening-blank-number').forEach((badge) => {
            const field = badge.closest('[data-question-number]');
            const number = Number(field?.dataset.questionNumber ?? 0);
            const item = byNumber.get(number) ?? {};
            badge.classList.remove('is-current', 'is-answered', 'is-flagged');

            if (number === currentQuestion) {
                badge.classList.add('is-current');
            } else if (item.is_flagged || item.status === 'flagged') {
                badge.classList.add('is-flagged');
            } else if (item.is_answered || item.status === 'answered') {
                badge.classList.add('is-answered');
            }
        });
    };

    const update = (items) => {
        state.palette = items;
        const currentQuestion = currentQuestionNumber();

        document.querySelectorAll('.listening-palette-item').forEach((btn) => {
            const number = Number(btn.dataset.questionNumber);
            const item = items.find((entry) => entry.question_number === number || entry.number === number);
            if (!item) return;

            const isCurrent = number === currentQuestion;
            applyClasses(btn, resolvePaletteStatus(item), isCurrent);
        });
        updatePartSummaries(items);
        updateBlankBadges(items, currentQuestion);
    };

    const setCurrent = (questionNumber) => {
        const section = state.sections?.find(
            (item) => questionNumber >= item.start_question_number && questionNumber <= item.end_question_number,
        )?.number ?? state.currentSection;

        if (Array.isArray(state.palette)) {
            state.palette = state.palette.map((item) => {
                const number = item.question_number ?? item.number;
                const isCurrent = number === questionNumber;

                return {
                    ...item,
                    is_current: isCurrent,
                    status: isCurrent
                        ? 'current'
                        : resolvePaletteStatus({ ...item, status: item.status === 'current' ? 'unanswered' : item.status }),
                };
            });
        }

        updatePartBoxes(section);

        document.querySelectorAll('.listening-palette-item').forEach((btn) => {
            const number = Number(btn.dataset.questionNumber);
            const item = state.palette?.find((entry) => (entry.question_number ?? entry.number) === number) ?? {};
            const isCurrent = number === questionNumber;
            applyClasses(btn, resolvePaletteStatus(item), isCurrent);
        });

        updateBlankBadges(state.palette ?? [], questionNumber);
    };

    const markAnswered = (questionNumber, answered = true) => {
        if (!Array.isArray(state.palette)) {
            return;
        }

        const items = state.palette.map((item) => {
            const number = item.question_number ?? item.number;

            if (number !== questionNumber) {
                return item;
            }

            const next = {
                ...item,
                is_answered: answered,
            };

            if (answered) {
                next.status = item.is_flagged || item.status === 'flagged' ? 'flagged' : 'answered';
            } else {
                next.status = item.is_flagged || item.status === 'flagged' ? 'flagged' : 'unanswered';
            }

            return next;
        });

        update(items);
    };

    const bind = () => {
        updatePartSummaries(state.palette ?? []);
        updatePartBoxes(state.currentSection ?? state.current_section_number ?? 1);
    };

    return { update, setCurrent, bind, updatePartBoxes, markAnswered };
}
