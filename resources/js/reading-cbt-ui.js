const PASSAGE_WIDTH_KEY = 'ielts-reading-cbt-passage-width';
const MIN_PASSAGE_WIDTH = 35;
const MAX_PASSAGE_WIDTH = 65;

function isTypingTarget(element) {
    if (!element) {
        return false;
    }

    const tag = element.tagName?.toLowerCase();

    return tag === 'input'
        || tag === 'textarea'
        || tag === 'select'
        || element.isContentEditable;
}

export function createReadingCbtUi(renderer) {
    let keydownHandler = null;

    const clampPassageWidth = (value) => Math.min(MAX_PASSAGE_WIDTH, Math.max(MIN_PASSAGE_WIDTH, value));

    const restorePassageWidth = () => {
        try {
            const stored = Number(localStorage.getItem(PASSAGE_WIDTH_KEY));

            if (!Number.isNaN(stored) && stored >= MIN_PASSAGE_WIDTH && stored <= MAX_PASSAGE_WIDTH) {
                renderer.passageWidth = stored;
            }
        } catch {
            // Ignore storage errors.
        }
    };

    const persistPassageWidth = () => {
        try {
            localStorage.setItem(PASSAGE_WIDTH_KEY, String(renderer.passageWidth));
        } catch {
            // Ignore storage errors.
        }
    };

    const bindKeyboardShortcuts = () => {
        if (keydownHandler) {
            window.removeEventListener('keydown', keydownHandler);
        }

        keydownHandler = (event) => {
            if (!event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) {
                return;
            }

            if (isTypingTarget(document.activeElement)) {
                return;
            }

            const key = event.key.toLowerCase();

            if (key === 'n') {
                event.preventDefault();
                renderer.goNext();
                return;
            }

            if (key === 'p') {
                event.preventDefault();
                renderer.goPrevious();
                return;
            }

            if (key === 'r') {
                event.preventDefault();
                renderer.openReview();
                return;
            }

            if (key === 'f') {
                event.preventDefault();
                const question = renderer.questions?.[renderer.activeQuestionIndex];

                if (question) {
                    renderer.toggleFlag(question.id, question.number);
                }
            }
        };

        window.addEventListener('keydown', keydownHandler);
    };

    const bind = () => {
        restorePassageWidth();
        bindKeyboardShortcuts();
        renderer.passageWidth = clampPassageWidth(renderer.passageWidth);
    };

    const destroy = () => {
        if (keydownHandler) {
            window.removeEventListener('keydown', keydownHandler);
            keydownHandler = null;
        }
    };

    return {
        bind,
        destroy,
        clampPassageWidth,
        persistPassageWidth,
    };
}
