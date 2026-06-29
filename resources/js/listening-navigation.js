export function createNavigation(state, ui, autosave) {
    const showQuestion = (number) => {
        state.currentQuestion = number;
        state.currentSection = number <= 10 ? 1 : number <= 20 ? 2 : number <= 30 ? 3 : 4;

        document.querySelectorAll('.listening-question').forEach((el) => {
            el.classList.toggle('hidden', Number(el.dataset.questionNumber) !== number);
        });

        document.querySelectorAll('.listening-palette-item').forEach((btn) => {
            btn.dataset.status = Number(btn.dataset.questionNumber) === number ? 'current' : btn.dataset.status;
            btn.classList.toggle('ring-2', Number(btn.dataset.questionNumber) === number);
        });

        document.querySelectorAll('.listening-section-tab').forEach((tab) => {
            tab.classList.toggle('bg-brand-50', Number(tab.dataset.section) === state.currentSection);
        });

        const startBtn = document.getElementById('listening-audio-start');
        if (startBtn) startBtn.disabled = false;
    };

    const bind = () => {
        document.getElementById('listening-prev')?.addEventListener('click', () => {
            autosave.bulkFlush();
            showQuestion(Math.max(1, state.currentQuestion - 1));
        });
        document.getElementById('listening-next')?.addEventListener('click', () => {
            autosave.bulkFlush();
            showQuestion(Math.min(40, state.currentQuestion + 1));
        });
        document.querySelectorAll('.listening-palette-item').forEach((btn) => {
            btn.addEventListener('click', () => {
                autosave.bulkFlush();
                showQuestion(Number(btn.dataset.questionNumber));
            });
        });
        document.querySelectorAll('.listening-section-tab').forEach((tab) => {
            tab.addEventListener('click', () => {
                const section = Number(tab.dataset.section);
                const firstQ = [1, 11, 21, 31][section - 1];
                showQuestion(firstQ);
            });
        });
    };

    return { showQuestion, bind };
}
