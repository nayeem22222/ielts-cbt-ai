const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

export function createAutosave(state, ui) {
    const debounceMs = state.config.auto_save_debounce_ms ?? 700;
    const intervalMs = (state.config.auto_save_interval_seconds ?? 10) * 1000;
    let debounceTimer = null;
    let pending = new Map();

    const flushSingle = async (questionId, answer) => {
        ui.setSaveStatus('Saving…');
        try {
            const res = await fetch(state.routes.save_answer, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify({
                    question_id: questionId,
                    student_answer: answer,
                    current_section_number: state.currentSection,
                    current_question_number: state.currentQuestion,
                }),
            });
            if (!res.ok) throw new Error('Save failed');
            ui.setSaveStatus('Saved');
            ui.hideOffline();
        } catch {
            ui.setSaveStatus('Retry pending');
            ui.showOffline();
        }
    };

    const queueSave = (questionId, answer) => {
        pending.set(questionId, answer);
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => flushSingle(questionId, answer), debounceMs);
    };

    const bulkFlush = async () => {
        if (pending.size === 0) return;
        const answers = [...pending.entries()].map(([question_id, student_answer]) => ({ question_id, student_answer }));
        pending.clear();
        ui.setSaveStatus('Saving…');
        try {
            await fetch(state.routes.bulk_save, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify({
                    answers,
                    current_section_number: state.currentSection,
                    current_question_number: state.currentQuestion,
                }),
            });
            ui.setSaveStatus('Saved');
            ui.hideOffline();
        } catch {
            ui.setSaveStatus('Offline');
            ui.showOffline();
        }
    };

    setInterval(bulkFlush, intervalMs);

    return { queueSave, bulkFlush };
}
