function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export const READING_TICKET_ISSUE_TYPES = [
    { value: 'wrong_answer', label: 'Wrong Answer' },
    { value: 'question_problem', label: 'Question Problem' },
    { value: 'typo', label: 'Typo' },
    { value: 'passage_problem', label: 'Passage Problem' },
    { value: 'explanation_problem', label: 'Explanation Problem' },
    { value: 'other', label: 'Other' },
];

export function createReadingTestTickets(renderer) {
    renderer.ticketModalOpen = false;
    renderer.ticketQuestionId = null;
    renderer.ticketQuestionNumber = null;
    renderer.ticketIssueType = 'question_problem';
    renderer.ticketMessage = '';
    renderer.ticketSubmitting = false;
    renderer.ticketSuccess = false;
    renderer.ticketIssueTypes = READING_TICKET_ISSUE_TYPES;

    const openModal = (questionId, questionNumber) => {
        renderer.ticketModalOpen = true;
        renderer.ticketQuestionId = questionId;
        renderer.ticketQuestionNumber = questionNumber;
        renderer.ticketIssueType = 'question_problem';
        renderer.ticketMessage = '';
        renderer.ticketSuccess = false;
    };

    const closeModal = () => {
        renderer.ticketModalOpen = false;
    };

    const submit = async () => {
        if (!renderer.endpoints?.tickets || !renderer.ticketQuestionId || !renderer.ticketMessage.trim()) {
            return;
        }

        renderer.ticketSubmitting = true;

        try {
            const response = await fetch(renderer.endpoints.tickets, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    question_id: renderer.ticketQuestionId,
                    issue_type: renderer.ticketIssueType,
                    message: renderer.ticketMessage,
                }),
            });

            if (response.ok) {
                renderer.ticketSuccess = true;
                setTimeout(() => closeModal(), 1200);
            }
        } finally {
            renderer.ticketSubmitting = false;
        }
    };

    const bind = () => {
        // Report buttons are rendered in Blade via <x-reading-test.report-question-button>.
    };

    return {
        openModal,
        closeModal,
        submit,
        bind,
    };
}
