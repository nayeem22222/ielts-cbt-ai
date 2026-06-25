function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export function createReadingTestNotes(renderer) {
    let saveTimer = null;

    renderer.notesPanelOpen = false;
    renderer.notesTab = 'all';
    renderer.noteDraft = {
        id: null,
        title: '',
        content: '',
        question_id: null,
        passage_id: null,
        selected_text: null,
        start_offset: null,
        end_offset: null,
    };

    const refreshAnchors = () => {
        renderer.highlightsController?.applyStoredHighlights(renderer.currentPassageId);
    };

    const filteredNotes = () => {
        const notes = renderer.notes ?? [];

        if (renderer.notesTab === 'passage') {
            return notes.filter((note) => note.passage_id && !note.question_id);
        }

        if (renderer.notesTab === 'question') {
            return notes.filter((note) => note.question_id);
        }

        return notes;
    };

    const scheduleSave = () => {
        if (renderer.isLocked) {
            return;
        }

        renderer.notesSaveStatus = 'saving';
        clearTimeout(saveTimer);
        saveTimer = setTimeout(() => persistDraft(), 600);
    };

    const persistDraft = async () => {
        if (!renderer.endpoints?.notes || !renderer.noteDraft.content?.trim()) {
            renderer.notesSaveStatus = 'saved';
            return;
        }

        renderer.notesSaveStatus = 'saving';

        const payload = {
            title: renderer.noteDraft.title || null,
            content: renderer.noteDraft.content,
            question_id: renderer.noteDraft.question_id,
            passage_id: renderer.noteDraft.passage_id,
            selected_text: renderer.noteDraft.selected_text ?? null,
            start_offset: renderer.noteDraft.start_offset ?? null,
            end_offset: renderer.noteDraft.end_offset ?? null,
        };

        if (renderer.noteDraft.id) {
            const url = renderer.endpoints.notesUpdate?.replace('__ID__', renderer.noteDraft.id);
            if (!url) {
                return;
            }

            const response = await fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    title: payload.title,
                    content: payload.content,
                }),
            });

            if (response.ok) {
                const json = await response.json();
                renderer.notes = (renderer.notes ?? []).map((note) => (note.id === json.data.id ? json.data : note));
                renderer.notesSaveStatus = 'saved';
                refreshAnchors();
            } else {
                renderer.notesSaveStatus = 'error';
            }

            return;
        }

        const response = await fetch(renderer.endpoints.notes, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(payload),
        });

        if (response.ok) {
            const json = await response.json();
            renderer.notes = [json.data, ...(renderer.notes ?? [])];
            renderer.noteDraft = {
                ...renderer.noteDraft,
                id: json.data.id,
                selected_text: json.data.selected_text,
                start_offset: json.data.start_offset,
                end_offset: json.data.end_offset,
            };
            renderer.notesSaveStatus = 'saved';
            refreshAnchors();
        } else {
            renderer.notesSaveStatus = 'error';
        }
    };

    const openPanel = (tab = 'all', seed = {}) => {
        renderer.notesPanelOpen = true;
        renderer.notesTab = tab === 'passage' || tab === 'question' ? tab : 'all';
        renderer.noteDraft = {
            id: null,
            title: seed.title ?? '',
            content: seed.content ?? '',
            question_id: seed.questionId ?? seed.question_id ?? null,
            passage_id: seed.passageId ?? seed.passage_id ?? renderer.currentPassageId,
            selected_text: seed.selected_text ?? seed.content ?? null,
            start_offset: seed.start_offset ?? null,
            end_offset: seed.end_offset ?? null,
        };
    };

    const closePanel = () => {
        renderer.notesPanelOpen = false;
        renderer.noteDraft = {
            id: null,
            title: '',
            content: '',
            question_id: null,
            passage_id: null,
            selected_text: null,
            start_offset: null,
            end_offset: null,
        };
        refreshAnchors();
    };

    const editNote = (note) => {
        renderer.noteDraft = {
            id: note.id,
            title: note.title ?? '',
            content: note.content ?? '',
            question_id: note.question_id,
            passage_id: note.passage_id,
            selected_text: note.selected_text,
            start_offset: note.start_offset,
            end_offset: note.end_offset,
        };
        renderer.notesPanelOpen = true;
        renderer.highlightsController?.scrollToNoteAnchor(note);
    };

    const newNote = () => {
        const question = renderer.questions?.[renderer.activeQuestionIndex];
        openPanel('all', {
            passageId: renderer.currentPassageId,
            questionId: question?.id ?? null,
            title: question ? `Question ${question.number}` : 'My note',
        });
    };

    const deleteNote = async (noteId) => {
        const url = renderer.endpoints?.notesDestroy?.replace('__ID__', noteId);
        if (!url) {
            return;
        }

        await fetch(url, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
        });

        renderer.notes = (renderer.notes ?? []).filter((note) => note.id !== noteId);
        refreshAnchors();
    };

    return {
        filteredNotes,
        scheduleSave,
        persistDraft,
        openPanel,
        closePanel,
        editNote,
        newNote,
        deleteNote,
    };
}
