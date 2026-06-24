function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function getPassageBody(passageId) {
    return document.querySelector(`.reading-passage-body[data-passage-id="${passageId}"]`);
}

function getQuestionAnchorContainer(questionId) {
    const input = document.querySelector(`[data-question-id="${questionId}"]`);
    return input?.closest('.reading-test-question-row') ?? null;
}

function getRangeOffset(container, range, end = false) {
    const probe = range.cloneRange();
    probe.selectNodeContents(container);
    probe.setEnd(range[end ? 'endContainer' : 'startContainer'], range[end ? 'endOffset' : 'startOffset']);
    return probe.toString().length;
}

function stripMarks(container) {
    container?.querySelectorAll('mark.reading-highlight, mark.reading-note-anchor').forEach((mark) => {
        mark.replaceWith(...mark.childNodes);
    });
}

function wrapTextRange(container, start, end, className, dataset = {}) {
    const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT);
    let cursor = 0;
    let startNode = null;
    let startOffset = 0;
    let endNode = null;
    let endOffset = 0;

    while (walker.nextNode()) {
        const node = walker.currentNode;
        const length = node.textContent?.length ?? 0;
        const nodeStart = cursor;
        const nodeEnd = cursor + length;

        if (startNode === null && start >= nodeStart && start <= nodeEnd) {
            startNode = node;
            startOffset = start - nodeStart;
        }

        if (endNode === null && end >= nodeStart && end <= nodeEnd) {
            endNode = node;
            endOffset = end - nodeStart;
            break;
        }

        cursor = nodeEnd;
    }

    if (!startNode || !endNode) {
        return null;
    }

    const range = document.createRange();
    range.setStart(startNode, startOffset);
    range.setEnd(endNode, endOffset);

    const mark = document.createElement('mark');
    mark.className = className;
    Object.entries(dataset).forEach(([key, value]) => {
        mark.dataset[key] = String(value);
    });

    try {
        range.surroundContents(mark);
        return mark;
    } catch {
        const fragment = range.extractContents();
        mark.appendChild(fragment);
        range.insertNode(mark);
        return mark;
    }
}

function noteAnchorContainer(note, renderer) {
    if (note.question_id) {
        return getQuestionAnchorContainer(note.question_id);
    }

    if (note.passage_id) {
        return getPassageBody(note.passage_id);
    }

    return null;
}

function hasNoteAnchor(note) {
    return note.start_offset != null
        && note.end_offset != null
        && note.end_offset > note.start_offset;
}

export function createReadingTestHighlights(renderer) {
    const state = {
        menuOpen: false,
        menuX: 0,
        menuY: 0,
        selectionText: '',
        selectionStart: 0,
        selectionEnd: 0,
        activePassageId: null,
        activeQuestionId: null,
        anchorType: 'passage',
    };

    const ensureMenu = () => {
        let menu = document.getElementById('reading-highlight-menu');
        if (menu) {
            return menu;
        }

        menu = document.createElement('div');
        menu.id = 'reading-highlight-menu';
        menu.className = 'reading-highlight-menu';
        menu.innerHTML = `
            <button type="button" data-color="yellow">Highlight Yellow</button>
            <button type="button" data-color="green">Highlight Green</button>
            <button type="button" data-color="blue">Highlight Blue</button>
            <button type="button" data-action="remove">Remove Highlight</button>
            <button type="button" data-action="note">Add Note</button>
        `;
        document.body.appendChild(menu);

        menu.addEventListener('mousedown', (event) => event.preventDefault());
        menu.addEventListener('click', async (event) => {
            const button = event.target.closest('button');
            if (!button) {
                return;
            }

            const color = button.dataset.color;
            const action = button.dataset.action;

            if (color) {
                await saveHighlight(color);
            } else if (action === 'remove') {
                await removeHighlightAtSelection();
            } else if (action === 'note') {
                openNoteFromSelection();
            }

            hideMenu();
        });

        return menu;
    };

    const hideMenu = () => {
        state.menuOpen = false;
        const menu = document.getElementById('reading-highlight-menu');
        if (menu) {
            menu.style.display = 'none';
        }
    };

    const showMenu = (x, y) => {
        const menu = ensureMenu();
        state.menuOpen = true;
        menu.style.display = 'flex';
        menu.style.left = `${x}px`;
        menu.style.top = `${y}px`;
    };

    const currentPassageId = () => renderer.currentPassageId;

    const applyNoteAnchors = (passageId) => {
        const notes = renderer.notes ?? [];
        const draft = renderer.noteDraft;

        notes.forEach((note) => {
            if (!hasNoteAnchor(note)) {
                return;
            }

            const belongsToPassage = note.passage_id === passageId
                || (note.question_id
                    && renderer.questions?.find((q) => q.id === note.question_id)?.passage_id === passageId);

            if (!belongsToPassage) {
                return;
            }

            const container = noteAnchorContainer(note, renderer);
            if (!container) {
                return;
            }

            wrapTextRange(
                container,
                note.start_offset,
                note.end_offset,
                'reading-note-anchor',
                { noteId: note.id },
            );
        });

        if (
            draft
            && !draft.id
            && draft.start_offset != null
            && draft.end_offset != null
            && (draft.passage_id === passageId || draft.question_id)
        ) {
            const container = draft.question_id
                ? getQuestionAnchorContainer(draft.question_id)
                : getPassageBody(draft.passage_id ?? passageId);

            if (container) {
                wrapTextRange(
                    container,
                    draft.start_offset,
                    draft.end_offset,
                    'reading-note-anchor reading-note-anchor--draft',
                );
            }
        }
    };

    const applyStoredHighlights = (passageId) => {
        const body = getPassageBody(passageId);
        if (body) {
            stripMarks(body);
        }

        document.querySelectorAll('.reading-test-question-row').forEach((row) => {
            const questionId = row.querySelector('[data-question-id]')?.dataset?.questionId;
            const question = renderer.questions?.find((q) => String(q.id) === String(questionId));
            if (question?.passage_id === passageId) {
                stripMarks(row);
            }
        });

        const items = (renderer.highlights ?? []).filter((item) => item.passage_id === passageId);
        items
            .sort((a, b) => a.start_offset - b.start_offset)
            .forEach((item) => {
                wrapTextRange(
                    body,
                    item.start_offset,
                    item.end_offset,
                    `reading-highlight reading-highlight--${item.highlight_color}`,
                    { highlightId: item.id },
                );
            });

        applyNoteAnchors(passageId);
    };

    const saveHighlight = async (color) => {
        if (!renderer.endpoints?.highlights || renderer.isLocked) {
            return;
        }

        const passageId = state.activePassageId ?? currentPassageId();
        const body = getPassageBody(passageId);
        if (!body || !state.selectionText) {
            return;
        }

        const response = await fetch(renderer.endpoints.highlights, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({
                passage_id: passageId,
                selected_text: state.selectionText,
                start_offset: state.selectionStart,
                end_offset: state.selectionEnd,
                highlight_color: color,
            }),
        });

        if (!response.ok) {
            return;
        }

        const payload = await response.json();
        renderer.highlights = [...(renderer.highlights ?? []).filter((item) => item.id !== payload.data.id), payload.data];
        applyStoredHighlights(passageId);
    };

    const removeHighlightAtSelection = async () => {
        const passageId = state.activePassageId ?? currentPassageId();

        const overlapping = (renderer.highlights ?? []).find(
            (item) =>
                item.passage_id === passageId
                && item.start_offset <= state.selectionStart
                && item.end_offset >= state.selectionEnd,
        );

        if (overlapping?.id && renderer.endpoints?.highlightsDestroy) {
            const url = renderer.endpoints.highlightsDestroy.replace('__ID__', overlapping.id);
            await fetch(url, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            });
            renderer.highlights = (renderer.highlights ?? []).filter((item) => item.id !== overlapping.id);
        }

        applyStoredHighlights(passageId);
        window.getSelection()?.removeAllRanges();
    };

    const openNoteFromSelection = () => {
        const passageId = state.activePassageId ?? currentPassageId();
        const question = state.activeQuestionId
            ? renderer.questions?.find((q) => q.id === state.activeQuestionId)
            : null;

        renderer.notesController?.openPanel(state.anchorType === 'question' ? 'question' : 'passage', {
            passageId,
            questionId: state.activeQuestionId ?? question?.id ?? null,
            title: state.anchorType === 'question'
                ? `Question ${question?.number ?? ''}`.trim()
                : 'Passage note',
            content: state.selectionText,
            selected_text: state.selectionText,
            start_offset: state.selectionStart,
            end_offset: state.selectionEnd,
        });

        renderer.$nextTick?.(() => applyStoredHighlights(passageId));
    };

    const scrollToNoteAnchor = (note) => {
        if (!hasNoteAnchor(note)) {
            return;
        }

        if (note.passage_id) {
            renderer.currentPassageId = note.passage_id;
        } else if (note.question_id) {
            const question = renderer.questions?.find((q) => q.id === note.question_id);
            if (question) {
                renderer.currentPassageId = question.passage_id;
                renderer.selectQuestion?.(question.number, false);
            }
        }

        renderer.$nextTick?.(() => {
            applyStoredHighlights(renderer.currentPassageId);
            const container = noteAnchorContainer(note, renderer);
            const mark = container?.querySelector(`mark[data-note-id="${note.id}"]`)
                ?? container?.querySelector('mark.reading-note-anchor');
            mark?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            mark?.classList.add('reading-note-anchor--pulse');
            setTimeout(() => mark?.classList.remove('reading-note-anchor--pulse'), 1200);
        });
    };

    const captureSelection = (container, range, passageId, questionId = null) => {
        state.activePassageId = passageId;
        state.activeQuestionId = questionId;
        state.anchorType = questionId ? 'question' : 'passage';
        state.selectionText = window.getSelection()?.toString() ?? '';
        state.selectionStart = getRangeOffset(container, range, false);
        state.selectionEnd = getRangeOffset(container, range, true);
    };

    const onMouseUp = (event) => {
        if (renderer.isLocked) {
            return;
        }

        const passageBody = event.target.closest('.reading-passage-body');
        const questionRow = event.target.closest('.reading-test-question-row');

        if (!passageBody && !questionRow) {
            hideMenu();
            return;
        }

        const selection = window.getSelection();
        if (!selection || selection.isCollapsed || !selection.toString().trim()) {
            const mark = event.target.closest('mark.reading-highlight, mark.reading-note-anchor');
            if (mark && passageBody) {
                const rect = mark.getBoundingClientRect();
                state.activePassageId = Number(passageBody.dataset.passageId);
                showMenu(rect.left + window.scrollX, rect.top + window.scrollY - 48);
                return;
            }

            hideMenu();
            return;
        }

        const range = selection.getRangeAt(0);

        if (questionRow && questionRow.contains(range.commonAncestorContainer)) {
            const questionId = Number(questionRow.querySelector('[data-question-id]')?.dataset?.questionId ?? 0);
            const question = renderer.questions?.find((q) => q.id === questionId);
            captureSelection(questionRow, range, question?.passage_id ?? renderer.currentPassageId, questionId || null);

            const rect = range.getBoundingClientRect();
            showMenu(rect.left + window.scrollX, rect.top + window.scrollY - 48);
            return;
        }

        if (!passageBody?.contains(range.commonAncestorContainer)) {
            hideMenu();
            return;
        }

        captureSelection(passageBody, range, Number(passageBody.dataset.passageId));

        const rect = range.getBoundingClientRect();
        showMenu(rect.left + window.scrollX, rect.top + window.scrollY - 48);
    };

    const bind = () => {
        ensureMenu();
        document.addEventListener('mouseup', onMouseUp);
        document.addEventListener('scroll', hideMenu, true);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                hideMenu();
            }
        });

        renderer.$watch?.('currentPassageId', (passageId) => {
            renderer.$nextTick?.(() => applyStoredHighlights(passageId));
        });

        renderer.$nextTick?.(() => applyStoredHighlights(renderer.currentPassageId));
    };

    return {
        bind,
        applyStoredHighlights,
        scrollToNoteAnchor,
        hideMenu,
    };
}

export { wrapTextRange, getPassageBody };
