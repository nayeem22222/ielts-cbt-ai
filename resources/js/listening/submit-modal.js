export function createListeningSubmitModal(state, autosave, navigation, review) {
    const modal = () => document.getElementById('listening-submit-modal');
    let reviewData = state.review ?? {};
    let submitting = false;

    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = String(value);
        }
    };

    const render = () => {
        const summary = reviewData.summary ?? {};
        setText('listening-submit-answered', summary.answered ?? 0);
        setText('listening-submit-unanswered', summary.unanswered ?? 0);
        setText('listening-submit-flagged', summary.flagged ?? 0);

        const unansweredBlock = document.getElementById('listening-submit-unanswered-block');
        const flaggedBlock = document.getElementById('listening-submit-flagged-block');
        const reviewBtn = document.getElementById('listening-submit-review-unanswered');
        const submitBtn = document.getElementById('listening-submit-confirm');

        const unansweredNumbers = reviewData.unanswered_numbers ?? [];
        const flaggedNumbers = reviewData.flagged_numbers ?? [];

        if (unansweredBlock) {
            unansweredBlock.classList.toggle('hidden', unansweredNumbers.length === 0);
            setText('listening-submit-unanswered-list', unansweredNumbers.join(', '));
        }

        if (flaggedBlock) {
            flaggedBlock.classList.toggle('hidden', flaggedNumbers.length === 0);
            setText('listening-submit-flagged-list', flaggedNumbers.join(', '));
        }

        if (reviewBtn) {
            reviewBtn.classList.toggle('hidden', unansweredNumbers.length === 0);
        }

        if (submitBtn && !submitting) {
            submitBtn.textContent = unansweredNumbers.length ? 'Submit anyway' : 'Submit test';
        }
    };

    const refreshReview = async () => {
        const url = state.routes?.review_summary;
        if (!url) {
            reviewData = state.review ?? {};
            render();
            return;
        }

        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) {
                render();
                return;
            }

            const body = await response.json();
            reviewData = body.data ?? reviewData;
            state.review = reviewData;
            render();
        } catch {
            render();
        }
    };

    const open = async () => {
        await refreshReview();
        modal()?.classList.remove('hidden');
        modal()?.classList.add('flex');
    };

    const close = () => {
        modal()?.classList.add('hidden');
        modal()?.classList.remove('flex');
    };

    const reviewUnanswered = async () => {
        const first = reviewData.unanswered_numbers?.[0];
        close();
        if (first) {
            await navigation.showQuestion(Number(first), 'jump');
        }
    };

    const submitAnyway = async () => {
        if (submitting) {
            return;
        }

        const preventUnsynced = state.config?.autosave?.prevent_submit_when_unsynced ?? true;
        if (preventUnsynced && autosave.hasUnsynced()) {
            const force = window.confirm('You have unsynced answers. Submit anyway?');
            if (!force) {
                return;
            }
        }

        submitting = true;
        const submitBtn = document.getElementById('listening-submit-confirm');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting…';
        }

        try {
            await autosave.flushBeforeNavigation();
            autosave.clearDraft();

            const response = await fetch(state.routes.submit, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
            });

            const body = await response.json();

            if (!response.ok) {
                window.alert(body?.message ?? 'Could not submit test. Please try again.');
                return;
            }

            const redirect = body?.data?.redirect_url;
            if (redirect) {
                window.location.href = redirect;
                return;
            }

            window.location.href = state.routes.result ?? window.location.href;
        } catch {
            window.alert('Could not submit test. Please try again.');
        } finally {
            submitting = false;
            if (submitBtn) {
                submitBtn.disabled = false;
            }
            close();
        }
    };

    const bind = () => {
        render();

        document.getElementById('listening-submit-open')?.addEventListener('click', () => {
            open();
        });

        document.getElementById('listening-submit-cancel')?.addEventListener('click', () => {
            close();
        });

        document.getElementById('listening-submit-review-unanswered')?.addEventListener('click', () => {
            reviewUnanswered();
        });

        document.getElementById('listening-submit-confirm')?.addEventListener('click', () => {
            submitAnyway();
        });

        modal()?.addEventListener('click', (event) => {
            if (event.target === modal()) {
                close();
            }
        });
    };

    const updateFromPalette = (items) => {
        if (!state.review?.summary) {
            return;
        }

        review.updateFromPalette(items);
        reviewData = state.review ?? reviewData;
        render();
    };

    return { bind, open, close, updateFromPalette, refreshReview };
}
