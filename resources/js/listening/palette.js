const statusClasses = {
    current: 'is-current',
    answered: 'is-answered',
    flagged: 'is-flagged',
    unanswered: '',
};

export function createPalette(state) {
    const applyClasses = (btn, status, isCurrent) => {
        btn.dataset.status = status;
        btn.classList.remove('is-current', 'is-answered', 'is-flagged');
        const resolved = isCurrent ? 'current' : status;
        const cls = statusClasses[resolved] ?? statusClasses.unanswered;
        if (cls) btn.classList.add(cls);
    };

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

    const update = (items) => {
        state.palette = items;
        document.querySelectorAll('.listening-palette-item').forEach((btn) => {
            const number = Number(btn.dataset.questionNumber);
            const item = items.find((entry) => entry.question_number === number || entry.number === number);
            if (!item) return;
            applyClasses(btn, item.status, item.is_current);
        });
        updatePartSummaries(items);
    };

    const setCurrent = (questionNumber) => {
        const section = state.sections?.find(
            (item) => questionNumber >= item.start_question_number && questionNumber <= item.end_question_number,
        )?.number ?? state.currentSection;

        updatePartBoxes(section);

        document.querySelectorAll('.listening-palette-item').forEach((btn) => {
            const number = Number(btn.dataset.questionNumber);
            const item = state.palette?.find((entry) => (entry.question_number ?? entry.number) === number) ?? {};
            const status = number === questionNumber ? 'current' : item.status ?? 'unanswered';
            applyClasses(btn, status === 'current' ? 'current' : status, number === questionNumber);
        });
    };

    const bind = () => {
        updatePartSummaries(state.palette ?? []);
        updatePartBoxes(state.currentSection ?? state.current_section_number ?? 1);
    };

    return { update, setCurrent, bind, updatePartBoxes };
}
