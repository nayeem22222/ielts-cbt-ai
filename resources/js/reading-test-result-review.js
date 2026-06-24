import { getPassageBody, wrapTextRange } from './reading-test-highlights';

export function readingTestResultReview(initialState = {}) {
    return {
        parts: initialState.parts ?? [],
        passages: initialState.passages ?? [],
        questionMap: initialState.questionMap ?? [],
        activeQuestionNumber: initialState.questionMap?.[0]?.question_number ?? null,
        activePassageId: initialState.passages?.[0]?.id ?? null,
        mobilePanel: 'questions',

        init() {
            if (this.activeQuestionNumber) {
                this.selectQuestion(this.activeQuestionNumber, false);
            }
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

            this.$nextTick(() => {
                const row = document.getElementById(`review-question-${number}`);
                row?.scrollIntoView({ behavior: 'smooth', block: 'start' });

                if (scrollPassage) {
                    this.highlightPassageReference();
                }
            });
        },

        highlightPassageReference() {
            const question = this.activeQuestion();
            const body = getPassageBody(this.activePassageId);
            if (!body) {
                return;
            }

            body.querySelectorAll('mark.reading-reference-highlight').forEach((mark) => {
                mark.replaceWith(...mark.childNodes);
            });

            if (
                question?.reference_start_offset != null
                && question?.reference_end_offset != null
                && question.reference_end_offset > question.reference_start_offset
            ) {
                wrapTextRange(
                    body,
                    question.reference_start_offset,
                    question.reference_end_offset,
                    'reading-reference-highlight',
                );
                body.querySelector('mark.reading-reference-highlight')?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                });
            } else if (question?.reference_paragraph) {
                const target = body.querySelector(`[data-paragraph="${question.reference_paragraph}"]`)
                    ?? [...body.querySelectorAll('p')].find((paragraph) =>
                        paragraph.textContent?.trim().startsWith(question.reference_paragraph),
                    );
                target?.classList.add('reading-paragraph-focus');
                target?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        },

        mapClass(item) {
            if (item.flagged || item.status === 'flagged') {
                return 'is-flagged';
            }

            return `is-${item.status}`;
        },
    };
}
