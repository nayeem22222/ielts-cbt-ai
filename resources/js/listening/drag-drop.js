function answerPayloadForInput(input) {
    const questionId = Number(input.dataset.questionId);

    if (input.dataset.itemKey) {
        return {
            questionId,
            answer: [{ item_key: input.dataset.itemKey, value: input.value, type: 'matching' }],
        };
    }

    if (input.dataset.answerType === 'letter') {
        return {
            questionId,
            answer: [{ value: input.value, type: 'letter' }],
        };
    }

    return {
        questionId,
        answer: [{ value: input.value, type: 'text' }],
    };
}

export function createListeningDragDrop({ questionArea, saveNow, palette }) {
    let selectedToken = null;
    let touchDragKey = null;

    const groupFor = (element) => element?.closest('.listening-dnd-group');

    const allowReuse = (groupEl) => groupEl?.dataset?.dndAllowReuse === '1';

    const optionKeysForGroup = (groupId) => {
        const keys = new Set();

        document.querySelectorAll(`.listening-dnd-token[data-group-id="${groupId}"]`).forEach((token) => {
            if (token.dataset.optionKey) {
                keys.add(token.dataset.optionKey);
            }
        });

        return keys;
    };

    const usedOptionKeys = (groupId, exceptQuestionId = null) => {
        const used = new Set();

        document.querySelectorAll(`.listening-dnd-dropzone[data-group-id="${groupId}"]`).forEach((zone) => {
            if (exceptQuestionId !== null && Number(zone.dataset.questionId) === exceptQuestionId) {
                return;
            }

            const input = zone.querySelector('.listening-dnd-input');
            const value = input?.value?.trim() ?? '';

            if (value !== '') {
                used.add(value);
            }
        });

        return used;
    };

    const setZoneState = (zone, state) => {
        zone.classList.remove(
            'listening-dnd-dropzone--empty',
            'listening-dnd-dropzone--filled',
            'listening-dnd-dropzone--hover',
            'listening-dnd-dropzone--invalid',
        );
        zone.classList.add(`listening-dnd-dropzone--${state}`);
    };

    const resolveOptionLabel = (groupId, optionKey) => {
        const token = document.querySelector(
            `.listening-dnd-token[data-group-id="${groupId}"][data-option-key="${CSS.escape(optionKey)}"]`,
        );

        return token?.dataset?.optionLabel ?? optionKey;
    };

    const syncTokenAvailability = (groupId) => {
        const groupEl = document.querySelector(`.listening-dnd-group[data-group-id="${groupId}"]`);
        const reuse = allowReuse(groupEl);
        const used = usedOptionKeys(groupId);

        document.querySelectorAll(`.listening-dnd-token[data-group-id="${groupId}"]`).forEach((token) => {
            const key = token.dataset.optionKey ?? '';
            const inUse = used.has(key);
            const unavailable = !reuse && inUse;

            token.classList.toggle('listening-dnd-token--used', unavailable);
            token.draggable = !unavailable;
            token.setAttribute('aria-disabled', unavailable ? 'true' : 'false');
        });
    };

    const syncZoneDisplay = (zone) => {
        const input = zone.querySelector('.listening-dnd-input');
        const placeholder = zone.querySelector('.listening-dnd-dropzone__placeholder');
        const filled = zone.querySelector('.listening-dnd-dropzone__filled');
        const keyEl = zone.querySelector('.listening-dnd-dropzone__key');
        const value = input?.value?.trim() ?? '';

        if (!value) {
            placeholder?.removeAttribute('hidden');
            filled?.setAttribute('hidden', '');
            setZoneState(zone, 'empty');
            syncTokenAvailability(zone.dataset.groupId);

            return;
        }

        const label = resolveOptionLabel(Number(zone.dataset.groupId), value);

        if (keyEl) {
            keyEl.textContent = label !== value ? `${value} ${label}` : value;
        }

        placeholder?.setAttribute('hidden', '');
        filled?.removeAttribute('hidden');
        setZoneState(zone, 'filled');
        syncTokenAvailability(zone.dataset.groupId);
    };

    const persistZone = (zone) => {
        const input = zone.querySelector('.listening-dnd-input');

        if (!input) {
            return;
        }

        const questionNumber = Number(zone.dataset.questionNumber);
        const { questionId, answer } = answerPayloadForInput(input);
        const hasAnswer = String(input.value ?? '').trim() !== '';

        palette?.markAnswered?.(questionNumber, hasAnswer);
        saveNow(questionId, hasAnswer ? answer : null);
    };

    const canAssign = (zone, optionKey) => {
        const groupId = Number(zone.dataset.groupId);

        if (!optionKeysForGroup(groupId).has(optionKey)) {
            return false;
        }

        const groupEl = groupFor(zone);

        if (allowReuse(groupEl)) {
            return true;
        }

        return !usedOptionKeys(groupId, Number(zone.dataset.questionId)).has(optionKey);
    };

    const assignToZone = (zone, optionKey) => {
        if (!zone || !optionKey || !canAssign(zone, optionKey)) {
            return false;
        }

        const input = zone.querySelector('.listening-dnd-input');

        if (!input) {
            return false;
        }

        input.value = optionKey;
        syncZoneDisplay(zone);
        persistZone(zone);

        return true;
    };

    const clearZone = (zone) => {
        const input = zone.querySelector('.listening-dnd-input');

        if (!input) {
            return;
        }

        input.value = '';
        syncZoneDisplay(zone);
        persistZone(zone);
    };

    const clearSelectedToken = () => {
        selectedToken?.classList.remove('listening-dnd-token--selected');
        selectedToken = null;
    };

    const bind = (root = questionArea) => {
        if (!root || root.dataset.listeningDndBound === '1') {
            return;
        }

        root.dataset.listeningDndBound = '1';

        root.querySelectorAll('.listening-dnd-dropzone').forEach(syncZoneDisplay);

        root.addEventListener('dragstart', (event) => {
            const token = event.target.closest('.listening-dnd-token');

            if (!token || token.classList.contains('listening-dnd-token--used')) {
                event.preventDefault();
                return;
            }

            const optionKey = token.dataset.optionKey ?? '';
            event.dataTransfer?.setData('text/plain', optionKey);
            event.dataTransfer?.setData('application/x-listening-option-key', optionKey);

            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
            }

            token.classList.add('listening-dnd-token--dragging');
        });

        root.addEventListener('dragend', (event) => {
            event.target.closest('.listening-dnd-token')?.classList.remove('listening-dnd-token--dragging');
            root.querySelectorAll('.listening-dnd-dropzone--hover').forEach((zone) => {
                zone.classList.remove('listening-dnd-dropzone--hover');
            });
        });

        root.addEventListener('dragover', (event) => {
            const zone = event.target.closest('.listening-dnd-dropzone');

            if (!zone) {
                return;
            }

            event.preventDefault();

            if (event.dataTransfer) {
                event.dataTransfer.dropEffect = 'move';
            }

            zone.classList.add('listening-dnd-dropzone--hover');
        });

        root.addEventListener('dragleave', (event) => {
            const zone = event.target.closest('.listening-dnd-dropzone');

            if (!zone) {
                return;
            }

            const related = event.relatedTarget;

            if (related instanceof Node && zone.contains(related)) {
                return;
            }

            zone.classList.remove('listening-dnd-dropzone--hover');
        });

        root.addEventListener('drop', (event) => {
            const zone = event.target.closest('.listening-dnd-dropzone');

            if (!zone) {
                return;
            }

            event.preventDefault();
            zone.classList.remove('listening-dnd-dropzone--hover');

            const optionKey = event.dataTransfer?.getData('application/x-listening-option-key')
                || event.dataTransfer?.getData('text/plain')
                || '';

            if (optionKey) {
                assignToZone(zone, optionKey);
                clearSelectedToken();
            }
        });

        root.addEventListener('click', (event) => {
            const clearButton = event.target.closest('.listening-dnd-dropzone__clear');

            if (clearButton) {
                event.preventDefault();
                event.stopPropagation();
                clearZone(clearButton.closest('.listening-dnd-dropzone'));
                clearSelectedToken();
                return;
            }

            const token = event.target.closest('.listening-dnd-token');

            if (token && !token.classList.contains('listening-dnd-token--used')) {
                if (selectedToken === token) {
                    clearSelectedToken();
                } else {
                    clearSelectedToken();
                    selectedToken = token;
                    token.classList.add('listening-dnd-token--selected');
                }

                return;
            }

            const zone = event.target.closest('.listening-dnd-dropzone');

            if (zone && selectedToken?.dataset?.optionKey) {
                assignToZone(zone, selectedToken.dataset.optionKey);
                clearSelectedToken();
            }
        });

        root.addEventListener('touchstart', (event) => {
            const token = event.target.closest('.listening-dnd-token');

            if (!token || token.classList.contains('listening-dnd-token--used')) {
                return;
            }

            touchDragKey = token.dataset.optionKey ?? null;
            token.classList.add('listening-dnd-token--dragging');
        }, { passive: true });

        root.addEventListener('touchend', (event) => {
            const token = event.target.closest('.listening-dnd-token');
            token?.classList.remove('listening-dnd-token--dragging');

            const touch = event.changedTouches?.[0];

            if (!touch || !touchDragKey) {
                touchDragKey = null;
                return;
            }

            const target = document.elementFromPoint(touch.clientX, touch.clientY);
            const zone = target?.closest('.listening-dnd-dropzone');

            if (zone) {
                assignToZone(zone, touchDragKey);
                clearSelectedToken();
            }

            touchDragKey = null;
        }, { passive: true });
    };

    const restore = (root = questionArea) => {
        root?.querySelectorAll('.listening-dnd-dropzone').forEach(syncZoneDisplay);
    };

    return { bind, restore };
}
