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

    const isMatchingHeadingsGroup = (groupEl) => groupEl?.dataset?.dndType === 'matching_headings'
        || groupEl?.dataset?.dndLayout === 'ielts-passage';

    const optionLabelsForGroup = (groupId) => {
        const config = document.querySelector(`.reading-dnd-headings-config[data-group-id="${groupId}"]`);
        if (!config?.dataset?.optionLabels) {
            return {};
        }

        try {
            return JSON.parse(config.dataset.optionLabels);
        } catch {
            return {};
        }
    };

    const resolveOptionLabel = (groupId, optionKey) => {
        const token = document.querySelector(
            `.reading-dnd-token[data-group-id="${groupId}"][data-option-key="${CSS.escape(optionKey)}"]`,
        );
        if (token?.dataset?.optionLabel) {
            return token.dataset.optionLabel;
        }

        const labels = optionLabelsForGroup(groupId);

        return labels[optionKey] ?? optionKey;
    };

    const allowReuseFor = (groupEl) => {
        if (isMatchingHeadingsGroup(groupEl)) {
            return false;
        }

        return groupEl?.dataset?.dndAllowReuse === '1';
    };

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
        const isHeadings = zone.classList.contains('reading-mh-passage-dropzone');

        if (!value) {
            placeholder?.removeAttribute('hidden');
            filled?.setAttribute('hidden', '');
            delete zone.dataset.assignedLabel;
            if (isHeadings) {
                zone.draggable = false;
            }
            setZoneState(zone, 'empty');
            return;
        }

        const token = document.querySelector(
            `.reading-dnd-token[data-group-id="${zone.dataset.groupId}"][data-option-key="${CSS.escape(value)}"]`,
        );
        const label = zone.dataset.assignedLabel || token?.dataset?.optionLabel || resolveOptionLabel(Number(zone.dataset.groupId), value);

        if (keyEl) {
            keyEl.textContent = isHeadings ? `${value}.` : value;
        }
        if (labelEl) {
            labelEl.textContent = label;
        }

        zone.dataset.assignedLabel = label;
        if (isHeadings) {
            zone.draggable = true;
        }

        placeholder?.setAttribute('hidden', '');
        filled?.removeAttribute('hidden');
        setZoneState(zone, 'filled');
    };

    const syncTokenAvailability = (groupId) => {
        const groupEl = document.querySelector(`.reading-dnd-group[data-group-id="${groupId}"]`);
        const used = usedOptionKeys(groupId);
        const isHeadings = isMatchingHeadingsGroup(groupEl);

        if (!isHeadings && (!groupEl || allowReuseFor(groupEl))) {
            document.querySelectorAll(`.reading-dnd-token[data-group-id="${groupId}"]`).forEach((token) => {
                token.classList.remove('reading-dnd-token--used');
                token.removeAttribute('aria-disabled');
            });

            return;
        }

        document.querySelectorAll(`.reading-dnd-token[data-group-id="${groupId}"]`).forEach((token) => {
            const key = token.dataset.optionKey ?? '';
            const isUsed = used.has(key);

            if (isHeadings) {
                const item = token.closest('.reading-mh-pool__item');
                token.classList.toggle('reading-mh-card--in-use', isUsed);
                if (item) {
                    item.hidden = isUsed;
                }
                token.setAttribute('aria-hidden', isUsed ? 'true' : 'false');

                return;
            }

            token.classList.toggle('reading-dnd-token--used', isUsed);
            if (isUsed) {
                token.setAttribute('aria-disabled', 'true');
            } else {
                token.removeAttribute('aria-disabled');
            }
        });
    };

    const clearZonesWithOptionKey = (groupId, optionKey, exceptZone = null) => {
        document.querySelectorAll(`.reading-dnd-dropzone[data-group-id="${groupId}"]`).forEach((zone) => {
            if (zone === exceptZone) {
                return;
            }

            const input = zone.querySelector('.reading-dnd-input');
            if (input?.value === optionKey) {
                input.value = '';
                syncZoneDisplay(zone);
                triggerSave(input);
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
        if (isLocked() || tokenEl.classList.contains('reading-dnd-token--used') || tokenEl.closest('.reading-mh-pool__item')?.hidden) {
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

        const groupId = Number(zone.dataset.groupId);

        if (isMatchingHeadingsGroup(groupRoot(zone))) {
            clearZonesWithOptionKey(groupId, optionKey, zone);
        }

        if (!validatePlacement(zone, optionKey)) {
            return false;
        }

        const input = zone.querySelector('.reading-dnd-input');
        if (!input) {
            return false;
        }

        const resolvedLabel = optionLabel || resolveOptionLabel(groupId, optionKey);

        const previous = input.value;
        input.value = optionKey;
        zone.dataset.assignedLabel = resolvedLabel;
        syncZoneDisplay(zone);
        syncTokenAvailability(groupId);
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
            if (isLocked() || token.classList.contains('reading-dnd-token--used') || token.closest('.reading-mh-pool__item')?.hidden) {
                event.preventDefault();
                return;
            }
            selectToken(token);
            token.classList.add('reading-dnd-token--dragging');
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

        if (zone.classList.contains('reading-mh-passage-dropzone')) {
            zone.draggable = false;

            zone.addEventListener('dragstart', (event) => {
                const input = zone.querySelector('.reading-dnd-input');
                if (isLocked() || !input?.value) {
                    event.preventDefault();
                    return;
                }

                const optionKey = input.value;
                event.dataTransfer?.setData('text/plain', optionKey);
                event.dataTransfer?.setData('application/x-reading-dnd', JSON.stringify({
                    optionKey,
                    optionLabel: zone.dataset.assignedLabel || resolveOptionLabel(Number(zone.dataset.groupId), optionKey),
                    groupId: zone.dataset.groupId,
                    sourceQuestionId: zone.dataset.questionId,
                }));
                if (event.dataTransfer) {
                    event.dataTransfer.effectAllowed = 'move';
                }
                zone.classList.add('reading-mh-passage-dropzone--dragging');
            });

            zone.addEventListener('dragend', () => {
                zone.classList.remove('reading-mh-passage-dropzone--dragging');
            });
        }

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
                const payloadData = event.dataTransfer?.getData('application/x-reading-dnd');
                if (payloadData) {
                    try {
                        const parsed = JSON.parse(payloadData);
                        if (parsed.sourceQuestionId && Number(parsed.sourceQuestionId) !== Number(zone.dataset.questionId)) {
                            const sourceZone = document.querySelector(
                                `.reading-dnd-dropzone[data-question-id="${parsed.sourceQuestionId}"]`,
                            );
                            const sourceInput = sourceZone?.querySelector('.reading-dnd-input');
                            if (sourceInput?.value === optionKey) {
                                sourceInput.value = '';
                                syncZoneDisplay(sourceZone);
                            }
                        }
                    } catch {
                        // ignore malformed drag payload
                    }
                }

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

    const restoreZoneFromSaved = (zone, input) => {
        const questionId = Number(zone.dataset.questionId ?? input?.dataset?.questionId ?? 0);
        if (!questionId) {
            return;
        }

        const saved = component.savedAnswers?.[questionId];
        if (saved?.answer && input && !input.value) {
            input.value = saved.answer;
        }
    };

    const paragraphLetterFromNode = (node) => {
        if (!node) {
            return '';
        }

        const dataRef = node.getAttribute?.('data-paragraph');
        if (dataRef) {
            return String(dataRef).trim().toUpperCase();
        }

        const labelEl = node.querySelector?.('.reading-passage-label');
        if (labelEl) {
            return labelEl.textContent.trim().toUpperCase();
        }

        const strong = node.querySelector?.('strong, b');
        if (strong) {
            const token = strong.textContent.trim().replace(/\.$/, '');
            if (/^[A-Z]$/.test(token)) {
                return token;
            }
        }

        const match = node.textContent?.trim().match(/^([A-Z])[.\s):\-–—]/);

        return match ? match[1] : '';
    };

    const collectPassageParagraphs = (passageBody) => {
        const labeled = [...passageBody.querySelectorAll('[data-paragraph], .reading-passage-paragraph')];
        if (labeled.length > 0) {
            return labeled;
        }

        const directBlocks = [...passageBody.children].filter((node) => {
            const tag = node.tagName?.toLowerCase() ?? '';

            return tag === 'p' || tag === 'div' || tag === 'blockquote' || tag === 'section';
        });

        if (directBlocks.length > 0) {
            return directBlocks;
        }

        return [...passageBody.querySelectorAll('p')];
    };

    const findPassageParagraph = (passageBody, ref, index, paragraphNodes) => {
        const nodes = paragraphNodes ?? collectPassageParagraphs(passageBody);
        const letter = String(ref ?? '').trim().toUpperCase();

        if (letter) {
            const byAttr = passageBody.querySelector(`[data-paragraph="${CSS.escape(letter)}"]`);
            if (byAttr) {
                return { paragraph: byAttr, ref: letter };
            }

            const byLetter = nodes.find((node) => paragraphLetterFromNode(node) === letter);
            if (byLetter) {
                return { paragraph: byLetter, ref: letter };
            }
        }

        const byIndex = nodes[index] ?? null;
        if (!byIndex) {
            return { paragraph: null, ref: letter };
        }

        return {
            paragraph: byIndex,
            ref: letter || paragraphLetterFromNode(byIndex),
        };
    };

    const injectPassageDropZones = () => {
        document.querySelectorAll('.reading-dnd-headings-config').forEach((config) => {
            if (config.dataset.dndPassageBound === '1') {
                return;
            }

            let questions = [];
            try {
                questions = JSON.parse(config.dataset.questions ?? '[]');
            } catch {
                questions = [];
            }

            if (questions.length === 0) {
                return;
            }

            const passageId = config.dataset.passageId;
            const passageBody = document.querySelector(`.reading-passage-body[data-passage-id="${passageId}"]`);
            if (!passageBody) {
                return;
            }

            let injected = 0;
            const paragraphNodes = collectPassageParagraphs(passageBody);

            questions.forEach((question, index) => {
                const { paragraph, ref } = findPassageParagraph(
                    passageBody,
                    question.paragraph_reference ?? '',
                    index,
                    paragraphNodes,
                );

                if (!paragraph) {
                    return;
                }

                if (passageBody.querySelector(`.reading-dnd-dropzone[data-question-id="${question.question_id}"]`)) {
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

                const badge = zone.querySelector('.reading-mh-dropzone__badge');
                if (badge) {
                    badge.textContent = `[${question.question_number}]`;
                }

                paragraph.parentNode?.insertBefore(clone, paragraph);
                bindDropzone(zone);
                restoreZoneFromSaved(zone, input);
                syncZoneDisplay(zone);
                injected += 1;
            });

            if (injected === questions.length) {
                config.dataset.dndPassageBound = '1';
            }
        });
    };

    const schedulePassageInjection = (attempts = 8) => {
        const run = (remaining) => {
            injectPassageDropZones();
            restoreAllZones();

            if (remaining <= 0) {
                return;
            }

            const pending = document.querySelector('.reading-dnd-headings-config:not([data-dnd-passage-bound="1"])');
            if (!pending) {
                return;
            }

            window.setTimeout(() => run(remaining - 1), 50);
        };

        run(attempts);
    };

    const restoreAllZones = () => {
        document.querySelectorAll('.reading-dnd-dropzone').forEach((zone) => {
            const input = zone.querySelector('.reading-dnd-input');
            restoreZoneFromSaved(zone, input);
            syncZoneDisplay(zone);
        });

        document.querySelectorAll('.reading-dnd-group').forEach((group) => {
            syncTokenAvailability(Number(group.dataset.groupId));
        });
    };

    const bindGroups = (root = document) => {
        root.querySelectorAll('.reading-dnd-token').forEach(bindToken);
        root.querySelectorAll('.reading-dnd-dropzone').forEach(bindDropzone);
        schedulePassageInjection();
    };

    const activateMatchingHeadingsLayout = () => {
        const layoutRoot = document.querySelector('[data-dnd-layout="ielts-passage"]');
        const shell = document.querySelector('.ielts-reading-cbt');

        if (!layoutRoot || !shell) {
            return;
        }

        shell.classList.add('is-matching-headings-dnd');
    };

    const init = (root = document) => {
        bindGroups(root);
        activateMatchingHeadingsLayout();

        if (component.autosave?.bindInputs) {
            component.autosave.bindInputs(root);
        }
    };

    return {
        init,
        restoreAllZones,
        bindGroups,
        schedulePassageInjection,
    };
}
