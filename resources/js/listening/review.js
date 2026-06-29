export function createListeningReview(state, navigation, palette) {
    let reviewOpen = false;
    const shell = () => document.getElementById('listening-review-shell');
    const panel = () => document.getElementById('listening-review-panel');

    const renderSummary = () => {
        const review = state.review ?? {};
        const summary = review.summary ?? {};

        document.getElementById('listening-review-total')?.replaceChildren(document.createTextNode(String(summary.total ?? 0)));
        document.getElementById('listening-review-answered')?.replaceChildren(document.createTextNode(String(summary.answered ?? 0)));
        document.getElementById('listening-review-unanswered')?.replaceChildren(document.createTextNode(String(summary.unanswered ?? 0)));
        document.getElementById('listening-review-flagged')?.replaceChildren(document.createTextNode(String(summary.flagged ?? 0)));
        document.getElementById('listening-review-not-visited')?.replaceChildren(document.createTextNode(String(summary.not_visited ?? 0)));

        const partsRoot = document.getElementById('listening-review-parts');
        if (!partsRoot) return;

        partsRoot.innerHTML = '';

        (review.parts ?? []).forEach((part) => {
            const section = document.createElement('div');
            section.className = 'listening-review-part';

            const heading = document.createElement('h3');
            heading.className = 'listening-review-part-title';
            heading.textContent = `${part.part_label}: ${part.question_range ?? ''}`;
            section.appendChild(heading);

            const grid = document.createElement('div');
            grid.className = 'listening-review-q-grid';

            (part.questions ?? []).forEach((question) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'listening-review-q';
                const status = question.current ? 'current' : (question.status ?? 'unanswered');
                button.classList.add(`is-${status}`);
                button.textContent = String(question.question_number);
                button.setAttribute('aria-label', `Go to question ${question.question_number}`);
                button.addEventListener('click', () => selectFromReview(question.question_number));
                grid.appendChild(button);
            });

            section.appendChild(grid);
            partsRoot.appendChild(section);
        });
    };

    const refreshReview = async () => {
        const url = state.routes?.review_summary;
        if (!url) {
            renderSummary();
            return;
        }

        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                return;
            }

            const body = await response.json();
            state.review = body.data ?? state.review;
            renderSummary();
        } catch {
            renderSummary();
        }
    };

    const openReview = async () => {
        await refreshReview();
        reviewOpen = true;
        shell()?.classList.remove('hidden');
        document.body.classList.add('listening-review-open');
    };

    const closeReview = () => {
        reviewOpen = false;
        shell()?.classList.add('hidden');
        document.body.classList.remove('listening-review-open');
    };

    const selectFromReview = async (questionNumber) => {
        closeReview();
        await navigation.showQuestion(Number(questionNumber), 'jump');
        await refreshReview();
    };

    const reviewUnanswered = async () => {
        const first = state.review?.unanswered_numbers?.[0];
        closeReview();
        if (first) {
            await navigation.showQuestion(Number(first), 'jump');
        }
    };

    const reviewFlagged = async () => {
        const first = state.review?.flagged_numbers?.[0];
        closeReview();
        if (first) {
            await navigation.showQuestion(Number(first), 'jump');
        }
    };

    const bind = () => {
        renderSummary();

        document.getElementById('listening-review-open')?.addEventListener('click', () => {
            openReview();
        });

        document.getElementById('listening-review-close')?.addEventListener('click', () => {
            closeReview();
        });

        document.getElementById('listening-review-backdrop')?.addEventListener('click', () => {
            closeReview();
        });

        document.getElementById('listening-review-unanswered-btn')?.addEventListener('click', () => {
            reviewUnanswered();
        });

        document.getElementById('listening-review-flagged-btn')?.addEventListener('click', () => {
            reviewFlagged();
        });

        document.getElementById('listening-review-continue-btn')?.addEventListener('click', () => {
            closeReview();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && reviewOpen) {
                closeReview();
            }
        });
    };

    const updateFromPalette = (items) => {
        if (!state.review?.summary) {
            return;
        }

        const answered = items.filter((item) => item.is_answered || item.status === 'answered').length;
        const flagged = items.filter((item) => item.is_flagged || item.status === 'flagged').length;
        const total = items.length;

        state.review.summary.answered = answered;
        state.review.summary.unanswered = Math.max(0, total - answered);
        state.review.summary.flagged = flagged;
        state.review.unanswered_numbers = items
            .filter((item) => !(item.is_answered || item.status === 'answered'))
            .map((item) => item.question_number)
            .sort((a, b) => a - b);
        state.review.flagged_numbers = items
            .filter((item) => item.is_flagged || item.status === 'flagged')
            .map((item) => item.question_number)
            .sort((a, b) => a - b);

        if (reviewOpen) {
            renderSummary();
        }
    };

    return { bind, refreshReview, openReview, closeReview, updateFromPalette };
}
