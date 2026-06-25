export function createReadingTestReview(component) {
    const refreshReview = async () => {
        if (!component.endpoints?.review) {
            return;
        }

        try {
            const response = await fetch(component.endpoints.review, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                return;
            }

            const body = await response.json();
            component.review = body.data ?? component.review;
        } catch {
            // Keep existing review snapshot.
        }
    };

    const openReview = async () => {
        await refreshReview();
        component.reviewOpen = true;
    };

    const closeReview = () => {
        component.reviewOpen = false;
    };

    const openSubmitModal = async () => {
        await refreshReview();
        component.submitModalOpen = true;
    };

    const closeSubmitModal = () => {
        component.submitModalOpen = false;
    };

    const reviewUnansweredFromPanel = () => {
        const first = component.review?.unanswered_numbers?.[0];
        component.submitModalOpen = false;
        component.reviewOpen = false;
        if (first) {
            component.selectQuestion(first);
        }
    };

    const submitAnyway = async () => {
        if (component.submitting || component.isLocked) {
            return;
        }

        component.submitting = true;

        try {
            const response = await fetch(component.endpoints.submit, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
            });

            const body = await response.json();

            if (!response.ok) {
                component.saveWarning = body?.message ?? 'Could not submit test. Please try again.';
                return;
            }

            const redirect = body?.data?.redirect_url;
            if (redirect) {
                window.location.href = redirect;
                return;
            }
        } catch {
            component.saveWarning = 'Could not submit test. Please try again.';
        } finally {
            component.submitting = false;
            component.submitModalOpen = false;
        }
    };

    const reviewFlaggedFromPanel = () => {
        const first = component.review?.flagged_numbers?.[0];
        component.reviewOpen = false;
        if (first) {
            component.selectQuestion(first);
        }
    };

    const selectFromReview = (questionNumber) => {
        component.reviewOpen = false;
        component.selectQuestion(questionNumber);
    };

    const pauseOverlay = () => {
        component.pauseOpen = !component.pauseOpen;
    };

    return {
        openReview,
        closeReview,
        openSubmitModal,
        closeSubmitModal,
        reviewUnansweredFromPanel,
        reviewFlaggedFromPanel,
        submitAnyway,
        selectFromReview,
        refreshReview,
        pauseOverlay,
    };
}
