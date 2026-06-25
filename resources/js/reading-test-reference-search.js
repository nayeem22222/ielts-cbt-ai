function stripReferenceMarkers(text) {
    return String(text ?? '')
        .replace(/\{\[([^\}]*)\}\[(\d+)\]/g, '$1')
        .replace(/\{\[([^\]]*)\]\[(\d+)\]\}/g, '$1')
        .replace(/\{\[([^\]]*)\]\}(\d+)\}\]/g, '$1')
        .replace(/\{\[([^\]]*)\]\}/g, '$1')
        .replace(/\]\[(\d+)\]\}/g, '')
        .replace(/\}\[(\d+)\]/g, '');
}

function normalizeSearchCharacter(character) {
    if (/[\u2010\u2011\u2012\u2013\u2014\u2212]/.test(character)) {
        return '-';
    }

    if (/[\u2018\u2019\u201C\u201D\u2032\u2033"'`]/.test(character)) {
        return null;
    }

    if (/[\u200B-\u200D\uFEFF]/.test(character)) {
        return null;
    }

    if (character === '\u00A0') {
        return ' ';
    }

    return character.toLowerCase();
}

function normalizeSearchText(text) {
    const normalized = [];

    for (const character of stripReferenceMarkers(text)) {
        const mapped = normalizeSearchCharacter(character);

        if (mapped === null) {
            continue;
        }

        normalized.push(mapped);
    }

    return normalized
        .join('')
        .replace(/\s+/g, ' ')
        .trim();
}

function buildNeedleVariants(searchText) {
    const base = normalizeSearchText(searchText);

    if (!base) {
        return [];
    }

    const variants = new Set([base]);

    for (const variant of [base.replace(/[.,;:!?]+$/u, ''), base.replace(/^[.,;:!?]+/u, '')]) {
        if (variant) {
            variants.add(variant);
        }
    }

    return [...variants];
}

function isInHeading(node) {
    return Boolean(node.parentElement?.closest('h1,h2,h3,h4'));
}

function collectTextNodes(container, { skipHeadings = true } = {}) {
    const nodes = [];
    const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT);

    while (walker.nextNode()) {
        const node = walker.currentNode;

        if (skipHeadings && isInHeading(node)) {
            continue;
        }

        nodes.push(node);
    }

    return nodes;
}

function createTextIndex(container, { skipHeadings = true } = {}) {
    const nodes = collectTextNodes(container, { skipHeadings });
    let text = '';

    for (const node of nodes) {
        text += node.textContent ?? '';
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

        const mapped = normalizeSearchCharacter(character);

        if (mapped === null || mapped === ' ') {
            continue;
        }

        normalizedChars.push(mapped);
        normalizedToOriginal.push(index);
        previousWasSpace = false;
    }

    while (normalizedChars.at(-1) === ' ') {
        normalizedChars.pop();
        normalizedToOriginal.pop();
    }

    return {
        text,
        nodes,
        normalized: normalizedChars.join(''),
        normalizedToOriginal,
        toOriginalRange(normalizedStart, normalizedEnd) {
            if (normalizedStart < 0 || normalizedEnd <= normalizedStart) {
                return null;
            }

            const start = normalizedToOriginal[normalizedStart];
            const endChar = normalizedToOriginal[normalizedEnd - 1];

            if (start === undefined || endChar === undefined) {
                return null;
            }

            return { start, end: endChar + 1 };
        },
    };
}

function createDomRangeFromOffsets(container, start, end, options = {}) {
    const nodes = collectTextNodes(container, options);
    let cursor = 0;
    let startNode = null;
    let startOffset = 0;
    let endNode = null;
    let endOffset = 0;

    for (const node of nodes) {
        const length = node.textContent?.length ?? 0;
        const nodeStart = cursor;
        const nodeEnd = cursor + length;

        if (startNode === null && start >= nodeStart && start < nodeEnd) {
            startNode = node;
            startOffset = start - nodeStart;
        }

        if (endNode === null && end > nodeStart && end <= nodeEnd) {
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

    return range;
}

export function sanitizePassageReferenceMarkers(container) {
    if (!container) {
        return;
    }

    for (const node of collectTextNodes(container, { skipHeadings: false })) {
        const cleaned = stripReferenceMarkers(node.textContent ?? '');

        if (cleaned !== node.textContent) {
            node.textContent = cleaned;
        }
    }
}

export function findNormalizedRange(container, searchText, options = {}) {
    if (!container) {
        return null;
    }

    const needles = buildNeedleVariants(searchText);

    if (needles.length === 0) {
        return null;
    }

    const index = createTextIndex(container, options);

    for (const needle of needles) {
        const position = index.normalized.indexOf(needle);

        if (position === -1) {
            continue;
        }

        const range = index.toOriginalRange(position, position + needle.length);

        if (range) {
            return range;
        }
    }

    return null;
}

export function findNormalizedDomRange(container, searchText, options = {}) {
    const raw = findNormalizedRange(container, searchText, options);

    if (!raw) {
        return null;
    }

    return createDomRangeFromOffsets(container, raw.start, raw.end, options);
}

export function normalizeParagraphLabel(label) {
    return String(label ?? '').trim().toUpperCase();
}

export function findParagraphBlock(body, label) {
    if (!body || !label) {
        return null;
    }

    const normalized = normalizeParagraphLabel(label);

    for (const block of body.querySelectorAll('[data-paragraph]')) {
        if (normalizeParagraphLabel(block.getAttribute('data-paragraph')) === normalized) {
            return block;
        }
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

function resolvePhraseSearchText(question, mode) {
    const phrase = question?.reference_phrase?.trim() ?? '';
    const sentence = question?.reference_sentence?.trim() ?? '';

    if (mode === 'sentence') {
        return sentence || phrase;
    }

    return phrase || sentence;
}

function findPhraseHighlightRange(body, question, mode) {
    const searchText = resolvePhraseSearchText(question, mode);

    if (!searchText) {
        return null;
    }

    const paragraph = question?.reference_paragraph?.trim();
    const options = { skipHeadings: true };
    const containers = [];

    if (paragraph) {
        const block = findParagraphBlock(body, paragraph);

        if (block) {
            containers.push(paragraphTextContainer(block));
        }
    }

    if (!containers.includes(body)) {
        containers.push(body);
    }

    for (const container of containers) {
        const range = findNormalizedRange(container, searchText, options);
        const domRange = findNormalizedDomRange(container, searchText, options);

        if (range && domRange) {
            return {
                container,
                start: range.start,
                end: range.end,
                domRange,
            };
        }
    }

    return null;
}

export function paragraphTextContainer(paragraphBlock) {
    return paragraphBlock?.querySelector('.reading-passage-paragraph-body') ?? paragraphBlock;
}

export function resolveSearchContainer(body, question, mode = null) {
    const paragraph = question?.reference_paragraph?.trim();

    if (!paragraph) {
        return body;
    }

    const block = findParagraphBlock(body, paragraph);

    if (block) {
        return paragraphTextContainer(block);
    }

    if (mode === 'phrase' || mode === 'sentence') {
        return null;
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
    const hasPhrase = Boolean(question?.reference_phrase?.trim());
    const hasSentence = Boolean(question?.reference_sentence?.trim());
    const hasOffsets = hasValidOffsets(question);

    if (type === 'phrase') {
        return 'phrase';
    }

    if (type === 'sentence') {
        return 'sentence';
    }

    if (hasPhrase) {
        return 'phrase';
    }

    if (hasSentence) {
        return 'sentence';
    }

    if (type === 'offset' && hasOffsets) {
        return 'offset';
    }

    if (hasOffsets) {
        return 'offset';
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
 * @returns {{ container: Element, start: number, end: number, domRange?: Range } | null}
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

    return findPhraseHighlightRange(body, question, mode);
}
