export function createReadingDragDrop(component) {
    /** @type {{ optionKey: string, optionLabel: string, groupId: number, tokenEl: HTMLElement } | null} */
    let selectedToken = null;

    const isLocked = () => component.isLocked === true;

    const isPreview = (root) => {
        if (document.querySelector('[data-dnd-preview="1"]')) {
            return true;
        }

        if (!root) {
            return false;
        }

        return root.dataset?.dndPreview === '1' || root.closest('[data-dnd-preview="1"]') !== null;
    };

    const groupRoot = (el) => el.closest('.reading-dnd-group');

    const allowReuseFor = (groupEl) => groupEl?.dataset?.dndAllowReuse === '1';

    const optionKeysForGroup = (groupId) => {
        const keys = new Set();
        document.querySelectorAll(`.reading-dnd-token[data-group-id="${groupId}"]`).forEach((token) => {
            if (token.dataset.optionKey) {
                keys.add(token.dataset.optionKey);
            }
        });
        return keys;
    };

    const usedOptionKeys = (groupId, exceptQuestionId = null) => {
        const used = new Set();
        document.querySelectorAll(`.reading-dnd-dropzone[data-group-id="${groupId}"]`).forEach((zone) => {
            if (exceptQuestionId !== null && Number(zone.dataset.questionId) === exceptQuestionId) {
                return;
            }
            const input = zone.querySelector('.reading-dnd-input');
            if (input?.value) {
                used.add(input.value);
            }
        });
        return used;
    };

    const setZoneState = (zone, state) => {
        zone.classList.remove(
            'reading-dnd-dropzone--empty',
            'reading-dnd-dropzone--filled',
            'reading-dnd-dropzone--hover',
            'reading-dnd-dropzone--invalid',
            'reading-dnd-dropzone--unsaved',
        );
        zone.classList.add(`reading-dnd-dropzone--${state}`);
    };

    const syncZoneDisplay = (zone) => {
        const input = zone.querySelector('.reading-dnd-input');
        const placeholder = zone.querySelector('.reading-dnd-dropzone__placeholder');
        const filled = zone.querySelector('.reading-dnd-dropzone__filled');
        const keyEl = zone.querySelector('.reading-dnd-dropzone__key');
        const labelEl = zone.querySelector('.reading-dnd-dropzone__label');
        const value = input?.value?.trim() ?? '';

        if (!value) {
            placeholder?.removeAttribute('hidden');
            filled?.setAttribute('hidden', '');
            setZoneState(zone, 'empty');
            return;
        }

        const token = document.querySelector(
            `.reading-dnd-token[data-group-id="${zone.dataset.groupId}"][data-option-key="${CSS.escape(value)}"]`,
        );
        const label = token?.dataset?.optionLabel ?? value;

        if (keyEl) {
            keyEl.textContent = value;
        }
        if (labelEl) {
            labelEl.textContent = label;
        }

        placeholder?.setAttribute('hidden', '');
        filled?.removeAttribute('hidden');
        setZoneState(zone, 'filled');
    };

    const syncTokenAvailability = (groupId) => {
        const groupEl = document.querySelector(`.reading-dnd-group[data-group-id="${groupId}"]`);
        if (!groupEl || allowReuseFor(groupEl)) {
            document.querySelectorAll(`.reading-dnd-token[data-group-id="${groupId}"]`).forEach((token) => {
                token.classList.remove('reading-dnd-token--used');
                token.removeAttribute('aria-disabled');
            });
            return;
        }

        const used = usedOptionKeys(groupId);
        document.querySelectorAll(`.reading-dnd-token[data-group-id="${groupId}"]`).forEach((token) => {
            const key = token.dataset.optionKey ?? '';
            const isUsed = used.has(key);
            token.classList.toggle('reading-dnd-token--used', isUsed);
            if (isUsed) {
                token.setAttribute('aria-disabled', 'true');
            } else {
                token.removeAttribute('aria-disabled');
            }
        });
    };

    const triggerSave = (input) => {
        if (!input || isLocked()) {
            return;
        }
        input.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const clearSelection = () => {
        if (selectedToken?.tokenEl) {
            selectedToken.tokenEl.classList.remove('reading-dnd-token--selected');
            selectedToken.tokenEl.setAttribute('aria-grabbed', 'false');
        }
        selectedToken = null;
    };

    const selectToken = (tokenEl) => {
        if (isLocked() || tokenEl.classList.contains('reading-dnd-token--used')) {
            return;
        }

        clearSelection();
        selectedToken = {
            optionKey: tokenEl.dataset.optionKey ?? '',
            optionLabel: tokenEl.dataset.optionLabel ?? '',
            groupId: Number(tokenEl.dataset.groupId),
            tokenEl,
        };
        tokenEl.classList.add('reading-dnd-token--selected');
        tokenEl.setAttribute('aria-grabbed', 'true');
    };

    const validatePlacement = (zone, optionKey) => {
        const groupId = Number(zone.dataset.groupId);
        const validKeys = optionKeysForGroup(groupId);

        if (!validKeys.has(optionKey)) {
            setZoneState(zone, 'invalid');
            window.setTimeout(() => syncZoneDisplay(zone), 600);
            return false;
        }

        const groupEl = groupRoot(zone);
        if (groupEl && !allowReuseFor(groupEl)) {
            const used = usedOptionKeys(groupId, Number(zone.dataset.questionId));
            if (used.has(optionKey)) {
                setZoneState(zone, 'invalid');
                window.setTimeout(() => syncZoneDisplay(zone), 600);
                return false;
            }
        }

        return true;
    };

    const assignToZone = (zone, optionKey, optionLabel = '') => {
        if (isLocked() || isPreview(groupRoot(zone))) {
            return false;
        }

        if (!validatePlacement(zone, optionKey)) {
            return false;
        }

        const input = zone.querySelector('.reading-dnd-input');
        if (!input) {
            return false;
        }

        const previous = input.value;
        input.value = optionKey;
        syncZoneDisplay(zone);
        syncTokenAvailability(Number(zone.dataset.groupId));
        clearSelection();

        if (previous !== optionKey) {
            triggerSave(input);
        }

        return true;
    };

    const clearZone = (zone) => {
        if (isLocked() || isPreview(groupRoot(zone))) {
            return;
        }

        const input = zone.querySelector('.reading-dnd-input');
        if (!input || !input.value) {
            return;
        }

        input.value = '';
        syncZoneDisplay(zone);
        syncTokenAvailability(Number(zone.dataset.groupId));
        triggerSave(input);
    };

    const bindToken = (token) => {
        if (token.dataset.dndBound === '1') {
            return;
        }
        token.dataset.dndBound = '1';

        token.addEventListener('dragstart', (event) => {
            if (isLocked() || token.classList.contains('reading-dnd-token--used')) {
                event.preventDefault();
                return;
            }
            selectToken(token);
            event.dataTransfer?.setData('text/plain', token.dataset.optionKey ?? '');
            event.dataTransfer?.setData('application/x-reading-dnd', JSON.stringify({
                optionKey: token.dataset.optionKey,
                optionLabel: token.dataset.optionLabel,
                groupId: token.dataset.groupId,
            }));
            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
            }
        });

        token.addEventListener('dragend', () => {
            token.classList.remove('reading-dnd-token--dragging');
        });

        token.addEventListener('click', () => selectToken(token));

        token.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                selectToken(token);
            }
        });
    };

    const bindDropzone = (zone) => {
        if (zone.dataset.dndBound === '1') {
            return;
        }
        zone.dataset.dndBound = '1';

        zone.addEventListener('dragover', (event) => {
            if (isLocked()) {
                return;
            }
            event.preventDefault();
            if (event.dataTransfer) {
                event.dataTransfer.dropEffect = 'move';
            }
            setZoneState(zone, zone.classList.contains('reading-dnd-dropzone--filled') ? 'filled' : 'hover');
            zone.classList.add('reading-dnd-dropzone--hover');
        });

        zone.addEventListener('dragleave', () => {
            syncZoneDisplay(zone);
        });

        zone.addEventListener('drop', (event) => {
            event.preventDefault();
            if (isLocked()) {
                return;
            }

            let optionKey = '';
            let optionLabel = '';

            const payload = event.dataTransfer?.getData('application/x-reading-dnd');
            if (payload) {
                try {
                    const parsed = JSON.parse(payload);
                    optionKey = parsed.optionKey ?? '';
                    optionLabel = parsed.optionLabel ?? '';
                } catch {
                    optionKey = event.dataTransfer?.getData('text/plain') ?? '';
                }
            } else {
                optionKey = event.dataTransfer?.getData('text/plain') ?? '';
            }

            if (!optionKey && selectedToken) {
                optionKey = selectedToken.optionKey;
                optionLabel = selectedToken.optionLabel;
            }

            if (optionKey) {
                assignToZone(zone, optionKey, optionLabel);
            }
        });

        zone.addEventListener('click', () => {
            if (isLocked()) {
                return;
            }
            if (selectedToken && Number(selectedToken.groupId) === Number(zone.dataset.groupId)) {
                assignToZone(zone, selectedToken.optionKey, selectedToken.optionLabel);
            }
        });

        zone.addEventListener('keydown', (event) => {
            if (isLocked()) {
                return;
            }
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                if (selectedToken && Number(selectedToken.groupId) === Number(zone.dataset.groupId)) {
                    assignToZone(zone, selectedToken.optionKey, selectedToken.optionLabel);
                }
            }
        });

        const removeBtn = zone.querySelector('.reading-dnd-dropzone__remove');
        removeBtn?.addEventListener('click', (event) => {
            event.stopPropagation();
            clearZone(zone);
        });
    };

    const injectPassageDropZones = () => {
        document.querySelectorAll('.reading-dnd-headings-config').forEach((config) => {
            if (config.dataset.dndPassageBound === '1') {
                return;
            }
            config.dataset.dndPassageBound = '1';

            let questions = [];
            try {
                questions = JSON.parse(config.dataset.questions ?? '[]');
            } catch {
                questions = [];
            }

            const passageId = config.dataset.passageId;
            const passageBody = document.querySelector(`.reading-passage-body[data-passage-id="${passageId}"]`);
            if (!passageBody) {
                return;
            }

            questions.forEach((question) => {
                const ref = String(question.paragraph_reference ?? '').trim().toUpperCase();
                if (!ref) {
                    return;
                }

                const paragraph = passageBody.querySelector(`[data-paragraph="${ref}"]`);
                if (!paragraph) {
                    return;
                }

                if (paragraph.querySelector(`[data-question-id="${question.question_id}"]`)) {
                    return;
                }

                const template = config.querySelector('template.reading-dnd-passage-template');
                if (!template) {
                    return;
                }

                const clone = template.content.cloneNode(true);
                const zone = clone.querySelector('.reading-dnd-dropzone');
                if (!zone) {
                    return;
                }

                zone.dataset.testId = String(question.test_id);
                zone.dataset.passageId = String(question.passage_id);
                zone.dataset.groupId = String(question.group_id);
                zone.dataset.questionId = String(question.question_id);
                zone.dataset.questionNumber = String(question.question_number);
                zone.dataset.questionType = String(question.question_type);
                zone.dataset.paragraphRef = ref;

                const input = zone.querySelector('.reading-dnd-input');
                if (input) {
                    input.dataset.testId = zone.dataset.testId;
                    input.dataset.passageId = zone.dataset.passageId;
                    input.dataset.groupId = zone.dataset.groupId;
                    input.dataset.questionId = zone.dataset.questionId;
                    input.dataset.questionNumber = zone.dataset.questionNumber;
                    input.dataset.questionType = zone.dataset.questionType;
                }

                const label = zone.querySelector('.reading-dnd-dropzone__paragraph-label');
                if (label) {
                    label.textContent = `Paragraph ${ref}`;
                }

                const body = paragraph.querySelector('.reading-passage-paragraph-body') ?? paragraph;
                body.insertBefore(clone, body.firstChild);
                bindDropzone(zone);
                syncZoneDisplay(zone);
            });
        });
    };

    const restoreAllZones = () => {
        document.querySelectorAll('.reading-dnd-dropzone').forEach((zone) => {
            syncZoneDisplay(zone);
        });

        document.querySelectorAll('.reading-dnd-group').forEach((group) => {
            syncTokenAvailability(Number(group.dataset.groupId));
        });
    };

    const bindGroups = (root = document) => {
        root.querySelectorAll('.reading-dnd-token').forEach(bindToken);
        root.querySelectorAll('.reading-dnd-dropzone').forEach(bindDropzone);
        injectPassageDropZones();
        restoreAllZones();
    };

    const init = (root = document) => {
        bindGroups(root);

        if (component.autosave?.bindInputs) {
            component.autosave.bindInputs(root);
        }
    };

    return {
        init,
        restoreAllZones,
        bindGroups,
    };
}
