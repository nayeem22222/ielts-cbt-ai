import { getPassageBody, wrapTextRange } from './reading-test-highlights';
import {
    findParagraphBlock,
    resolveHighlightRange,
} from './reading-test-reference-search';

function clearPassageHighlights(body) {
    body.querySelectorAll('mark.reading-reference-highlight, mark.reading-highlight').forEach((mark) => {
        mark.querySelector('.reading-reference-label')?.remove();
        const fragment = document.createDocumentFragment();

        while (mark.firstChild) {
            fragment.appendChild(mark.firstChild);
        }

        mark.replaceWith(fragment);
    });

    body.querySelectorAll('.reading-reference-label').forEach((label) => label.remove());

    body.querySelectorAll('.reading-paragraph-focus').forEach((element) => {
        element.classList.remove('reading-paragraph-focus');
    });
}

function wrapReferenceHighlight(container, start, end, questionNumber) {
    const mark = wrapTextRange(
        container,
        start,
        end,
        'reading-reference-highlight reading-highlight',
        { questionNumber },
    );

    if (!mark) {
        return null;
    }

    const label = document.createElement('strong');
    label.className = 'reading-reference-label';
    label.textContent = `Q${questionNumber}`;
    mark.prepend(label);

    return mark;
}

export function readingTestResultReview(initialState = {}) {
    return {
        parts: initialState.parts ?? [],
        passages: initialState.passages ?? [],
        questionMap: initialState.questionMap ?? [],
        activeQuestionNumber: initialState.questionMap?.[0]?.question_number ?? null,
        activePassageId: initialState.passages?.[0]?.id ?? null,
        mobilePanel: 'questions',
        _renderFrameId: null,

        init() {
            this.$watch('activePassageId', () => {
                this.schedulePassageReferenceRender();
            });

            this.schedulePassageReferenceRender(true);
        },

        schedulePassageReferenceRender(focusActive = false) {
            if (this._renderFrameId) {
                cancelAnimationFrame(this._renderFrameId);
                this._renderFrameId = null;
            }

            this.$nextTick(() => {
                this._renderFrameId = requestAnimationFrame(() => {
                    this._renderFrameId = null;
                    this.renderPassageReferenceHighlights();

                    if (focusActive && this.activeQuestionNumber) {
                        this.focusQuestionReference(this.activeQuestionNumber, false);
                    }
                });
            });
        },

        questionsForPassage(passageId) {
            const part = this.parts.find((item) => item.passage_id === passageId);

            return part?.questions ?? [];
        },

        activeQuestion() {
            for (const part of this.parts) {
                for (const item of part.questions ?? []) {
                    if (item.question_number === this.activeQuestionNumber) {
                        return item;
                    }
                }
            }

            return null;
        },

        selectQuestion(number, scrollPassage = true) {
            const mapItem = this.questionMap.find((item) => item.question_number === number);
            this.activeQuestionNumber = number;

            if (mapItem?.passage_id) {
                this.activePassageId = mapItem.passage_id;
            }

            if (scrollPassage) {
                this.mobilePanel = 'passage';
            }

            this.$nextTick(() => {
                const row = document.getElementById(`review-question-${number}`);
                row?.scrollIntoView({ behavior: 'smooth', block: 'start' });

                if (scrollPassage) {
                    this.focusQuestionReference(number);
                }
            });
        },

        renderPassageReferenceHighlights() {
            const body = getPassageBody(this.activePassageId);

            if (!body) {
                return;
            }

            clearPassageHighlights(body);

            const seen = new Set();
            const jobs = [];

            for (const question of this.questionsForPassage(this.activePassageId)) {
                if (seen.has(question.question_number)) {
                    continue;
                }

                seen.add(question.question_number);

                const range = resolveHighlightRange(body, question);

                if (!range) {
                    continue;
                }

                jobs.push({
                    question,
                    container: range.container,
                    start: range.start,
                    end: range.end,
                });
            }

            const byContainer = new Map();

            for (const job of jobs) {
                if (!byContainer.has(job.container)) {
                    byContainer.set(job.container, []);
                }

                byContainer.get(job.container).push(job);
            }

            for (const [container, containerJobs] of byContainer) {
                containerJobs.sort((a, b) => b.start - a.start);

                for (const { question, start, end } of containerJobs) {
                    wrapReferenceHighlight(container, start, end, question.question_number);
                }
            }
        },

        focusQuestionReference(number, scroll = true) {
            if (this._renderFrameId) {
                cancelAnimationFrame(this._renderFrameId);
                this._renderFrameId = null;
            }

            requestAnimationFrame(() => {
                const body = getPassageBody(this.activePassageId);

                if (!body) {
                    return;
                }

                body.querySelectorAll('mark.reading-reference-highlight, mark.reading-highlight').forEach((mark) => {
                    mark.classList.remove('is-active');
                });

                body.querySelectorAll('.reading-paragraph-focus').forEach((element) => {
                    element.classList.remove('reading-paragraph-focus');
                });

                const mark = body.querySelector(
                    `mark.reading-reference-highlight[data-question-number="${number}"], mark.reading-highlight[data-question-number="${number}"]`,
                );

                if (mark) {
                    mark.classList.add('is-active');

                    if (scroll) {
                        mark.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }

                    return;
                }

                const question = this.questionsForPassage(this.activePassageId)
                    .find((item) => item.question_number === number);

                const paragraphBlock = question?.reference_paragraph
                    ? findParagraphBlock(body, question.reference_paragraph)
                    : null;

                if (paragraphBlock) {
                    paragraphBlock.classList.add('reading-paragraph-focus');

                    if (scroll) {
                        paragraphBlock.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
        },

        mapClass(item) {
            if (item.flagged || item.status === 'flagged') {
                return 'is-flagged';
            }

            return `is-${item.status}`;
        },
    };
}
