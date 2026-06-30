const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

export function createRecovery(state, autosave, navigation) {
    const draftKey = () => state.recovery?.draft_key ?? `listening_attempt_${state.attempt_id}_draft`;

    const loadDraft = () => {
        try {
            const raw = localStorage.getItem(draftKey());
            return raw ? JSON.parse(raw) : null;
        } catch {
            return null;
        }
    };

    const showModal = (unsavedAnswers) => {
        const modal = document.getElementById('listening-recovery-modal');
        const list = document.getElementById('listening-recovery-list');
        if (!modal || !list) return;

        list.innerHTML = unsavedAnswers
            .map((item) => `<li>Question ${item.question_number}</li>`)
            .join('');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    const hideModal = () => {
        const modal = document.getElementById('listening-recovery-modal');
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
    };

    const applyRecovery = async (answers) => {
        await fetch(state.routes.state_sync, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf(),
            },
            body: JSON.stringify({
                current_section_number: state.currentSection,
                current_question_number: state.activeQuestionNumber ?? state.currentQuestion,
                recover_answers: answers,
            }),
        });
        hideModal();
        window.location.reload();
    };

    const init = async () => {
        if (!state.recovery?.enabled) return;

        const draft = loadDraft();
        if (!draft || !draft.answers) return;

        try {
            const res = await fetch(state.routes.state_sync, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify({
                    current_section_number: state.currentSection,
                    current_question_number: state.activeQuestionNumber ?? state.currentQuestion,
                    client_draft: draft,
                }),
            });

            const data = await res.json();
            const unsaved = data.recovery?.unsaved_answers ?? [];

            if (unsaved.length > 0 && state.recovery?.show_modal) {
                showModal(unsaved);

                document.getElementById('listening-recovery-restore')?.addEventListener('click', () => {
                    applyRecovery(unsaved);
                });
                document.getElementById('listening-recovery-discard')?.addEventListener('click', () => {
                    autosave.clearDraft();
                    hideModal();
                });
            }
        } catch {
            // Server snapshot remains source of truth.
        }
    };

    return { init, loadDraft, hideModal };
}
