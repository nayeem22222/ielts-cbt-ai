export function listeningTestResultReview(initialState = {}) {
    return {
        sections: initialState.sections ?? [],
        questionMap: initialState.questionMap ?? [],
        activeQuestionNumber: initialState.questionMap?.[0]?.question_number ?? null,
        activeSectionId: initialState.sections?.[0]?.id ?? null,
        mobilePanel: 'questions',

        init() {
            const hash = window.location.hash.match(/^#question-(\d+)$/);
            if (hash) {
                this.selectQuestion(Number(hash[1]));
            }
        },

        mapClass(item) {
            return `is-${item.status ?? 'unanswered'}`;
        },

        selectQuestion(questionNumber, sectionId = null) {
            this.activeQuestionNumber = Number(questionNumber);

            if (sectionId) {
                this.activeSectionId = Number(sectionId);
            } else {
                const item = this.questionMap.find((entry) => entry.question_number === this.activeQuestionNumber);
                if (item?.section_id) {
                    this.activeSectionId = item.section_id;
                }
            }

            const target = document.getElementById(`question-${this.activeQuestionNumber}`);
            target?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        },
    };
}

window.listeningTestResultReview = listeningTestResultReview;
