function normalizeSearchText(text) {
    return String(text ?? '')
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase();
}

function isInHeading(node) {
    return Boolean(node.parentElement?.closest('h1,h2,h3,h4'));
}

function createTextIndex(container, { skipHeadings = true } = {}) {
    const segments = [];
    let text = '';
    const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT);

    while (walker.nextNode()) {
        const node = walker.currentNode;

        if (skipHeadings && isInHeading(node)) {
            continue;
        }

        const content = node.textContent ?? '';
        segments.push({ node, start: text.length, end: text.length + content.length });
        text += content;
    }

    const normalizedChars = [];
    const normalizedToOriginal = [];
    let previousWasSpace = false;

    for (let index = 0; index < text.length; index += 1) {
        const character = text[index];

        if (/\s/.test(character)) {
            if (!previousWasSpace && normalizedChars.length > 0) {
                normalizedChars.push(' ');
                normalizedToOriginal.push(index);
                previousWasSpace = true;
            }

            continue;
        }

        normalizedChars.push(character.toLowerCase());
        normalizedToOriginal.push(index);
        previousWasSpace = false;
    }

    while (normalizedChars.at(-1) === ' ') {
        normalizedChars.pop();
        normalizedToOriginal.pop();
    }

    const normalized = normalizedChars.join('');

    return {
        text,
        segments,
        normalized,
        normalizedToOriginal,
        toOriginalRange(normalizedStart, normalizedEnd) {
            if (normalizedStart < 0 || normalizedEnd <= normalizedStart) {
                return null;
            }

            const start = normalizedToOriginal[normalizedStart];
            const endIndex = normalizedEnd - 1;
            const endChar = normalizedToOriginal[endIndex];

            if (start === undefined || endChar === undefined) {
                return null;
            }

            return { start, end: endChar + 1 };
        },
    };
}

export function findNormalizedRange(container, searchText, options = {}) {
    const needle = normalizeSearchText(searchText);

    if (!needle || !container) {
        return null;
    }

    const index = createTextIndex(container, options);
    const position = index.normalized.indexOf(needle);

    if (position === -1) {
        return null;
    }

    return index.toOriginalRange(position, position + needle.length);
}

export function normalizeParagraphLabel(label) {
    return String(label ?? '').trim().toUpperCase();
}

export function findParagraphBlock(body, label) {
    if (!body || !label) {
        return null;
    }

    const normalized = normalizeParagraphLabel(label);
    const byData = body.querySelector(`[data-paragraph="${normalized}"]`);

    if (byData) {
        return byData;
    }

    for (const block of body.querySelectorAll('.reading-passage-paragraph')) {
        const labelEl = block.querySelector('.reading-passage-label');

        if (normalizeParagraphLabel(labelEl?.textContent) === normalized) {
            return block;
        }
    }

    return [...body.querySelectorAll('p')].find((paragraph) =>
        normalizeParagraphLabel(paragraph.textContent?.trim().charAt(0)) === normalized
        || paragraph.textContent?.trim().toUpperCase().startsWith(`${normalized} `),
    ) ?? null;
}

export function paragraphTextContainer(paragraphBlock) {
    return paragraphBlock?.querySelector('.reading-passage-paragraph-body') ?? paragraphBlock;
}

export function resolveSearchContainer(body, question) {
    const paragraph = question?.reference_paragraph?.trim();

    if (paragraph) {
        const block = findParagraphBlock(body, paragraph);

        if (block) {
            return paragraphTextContainer(block);
        }
    }

    return body;
}

export function hasValidOffsets(question) {
    return question?.reference_start_offset != null
        && question?.reference_end_offset != null
        && question.reference_end_offset > question.reference_start_offset;
}

export function resolveReferenceMode(question) {
    const type = question?.reference_type;

    if (type === 'phrase' || type === 'sentence' || type === 'offset') {
        return type;
    }

    if (hasValidOffsets(question)) {
        return 'offset';
    }

    if (question?.reference_phrase?.trim()) {
        return 'phrase';
    }

    if (question?.reference_sentence?.trim()) {
        return 'sentence';
    }

    return null;
}

export function offsetSearchContainer(body, question) {
    const paragraph = question?.reference_paragraph?.trim();

    if (paragraph) {
        const block = findParagraphBlock(body, paragraph);

        if (!block) {
            return null;
        }

        return paragraphTextContainer(block);
    }

    return body;
}

/**
 * @returns {{ container: Element, start: number, end: number } | null}
 */
export function resolveHighlightRange(body, question) {
    const mode = resolveReferenceMode(question);

    if (!mode) {
        return null;
    }

    if (mode === 'offset') {
        if (!hasValidOffsets(question)) {
            return null;
        }

        const container = offsetSearchContainer(body, question);

        if (!container) {
            return null;
        }

        return {
            container,
            start: question.reference_start_offset,
            end: question.reference_end_offset,
        };
    }

    const searchText = mode === 'phrase'
        ? question.reference_phrase
        : question.reference_sentence;

    const container = resolveSearchContainer(body, question);
    const range = findNormalizedRange(container, searchText, { skipHeadings: true });

    if (!range) {
        return null;
    }

    return {
        container,
        start: range.start,
        end: range.end,
    };
}
