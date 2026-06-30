import '../css/reading-test-renderer.css';
import '../css/reading-cbt-ui.css';
import { createReadingTestAutosave } from './reading-test-autosave';
import { createReadingTestTimer } from './reading-test-timer';
import { createReadingTestReview } from './reading-test-review';
import { createReadingTestHighlights } from './reading-test-highlights';
import { createReadingTestNotes } from './reading-test-notes';
import { createReadingTestTickets } from './reading-test-tickets';
import { createReadingCbtUi } from './reading-cbt-ui';
import { sanitizePassageReferenceMarkers } from './reading-test-reference-search';
import { createReadingDragDrop } from './reading-drag-drop';

export function readingTestRenderer(initialState = {}) {
    return {
        testId: initialState.testId ?? null,
        testTitle: initialState.testTitle ?? 'Reading Test',
        examType: initialState.examType ?? '',
        durationMinutes: initialState.durationMinutes ?? 60,
        passages: initialState.passages ?? [],
        questions: initialState.questions ?? [],
        attemptId: initialState.attemptId ?? null,
        attemptStatus: initialState.attemptStatus ?? 'in_progress',
        isLocked: initialState.isLocked ?? false,
        endpoints: initialState.endpoints ?? {},
        savedAnswers: initialState.savedAnswers ?? {},
        navigator: initialState.navigator ?? { questions: {}, parts: {} },
        review: initialState.review ?? { summary: {}, parts: [], unanswered_numbers: [], flagged_numbers: [] },
        timer: initialState.timer ?? {},
        visitedQuestions: initialState.visitedQuestions ?? [],
        highlights: initialState.highlights ?? [],
        notes: initialState.notes ?? [],
        saveWarning: null,
        remainingSeconds: initialState.timer?.remaining_seconds ?? 0,
        timerLabel: '00:00',
        timerClassName: 'reading-test-timer',
        reviewOpen: false,
        submitModalOpen: false,
        pauseOpen: false,
        submitting: false,
        autoSubmitting: false,
        currentPassageId: initialState.initialPassageId ?? null,
        currentQuestionNumber: initialState.initialQuestionNumber ?? null,
        activeQuestionIndex: 0,
        expandedPartId: initialState.initialPassageId ?? null,
        mobilePanel: 'passage',
        passageWidth: 50,
        resizing: false,
        autosave: null,
        timerController: null,
        reviewController: null,
        highlightsController: null,
        notesController: null,
        ticketsController: null,
        cbtUi: null,
        dragDrop: null,
        notesPanelOpen: false,
        notesTab: 'all',
        noteDraft: { id: null, title: '', content: '', question_id: null, passage_id: null, selected_text: null, start_offset: null, end_offset: null },
        ticketModalOpen: false,
        ticketIssueType: 'question_problem',
        ticketMessage: '',
        ticketSubmitting: false,
        ticketSuccess: false,
        ticketQuestionId: null,
        ticketQuestionNumber: null,
        ticketIssueTypes: [],
        notesSaveStatus: 'saved',

        init() {
            if (this.isLocked && this.endpoints?.result) {
                window.location.href = this.endpoints.result;
                return;
            }

            this.autosave = createReadingTestAutosave(this);
            this.timerController = createReadingTestTimer(this);
            this.reviewController = createReadingTestReview(this);
            this.highlightsController = createReadingTestHighlights(this);
            this.notesController = createReadingTestNotes(this);
            this.ticketsController = createReadingTestTickets(this);
            this.cbtUi = createReadingCbtUi(this);
            this.dragDrop = createReadingDragDrop(this);
            this.cbtUi.bind();

            if (this.currentQuestionNumber) {
                this.activeQuestionIndex = this.questions.findIndex(
                    (q) => q.number === this.currentQuestionNumber,
                );
                if (this.activeQuestionIndex < 0) {
                    this.activeQuestionIndex = 0;
                }
            }

            this.syncPassageForQuestion();
            this.$nextTick(() => {
                this.autosave.restoreAnswers();
                this.autosave.bindInputs();
                this.dragDrop?.init();
                this.bindQuestionInteractions();
                this.highlightCurrentQuestion();
                this.markVisitedForCurrentQuestion();
                this.timerController.start();

                if (this.isLocked) {
                    this.timerController.lockInputs();
                }

                this.highlightsController.bind();
                this.ticketsController.bind();
                this.sanitizePassageMarkers();
                this.syncSplitLayout();
            });
        },

        sanitizePassageMarkers() {
            document.querySelectorAll('.reading-passage-body').forEach((body) => {
                sanitizePassageReferenceMarkers(body);
            });
        },

        isDesktop() {
            return window.innerWidth >= 1024;
        },

        syncSplitLayout() {
            const container = this.$refs.splitContainer;

            if (!container) {
                return;
            }

            if (!this.isDesktop()) {
                container.style.gridTemplateColumns = '';

                return;
            }

            const left = this.passageWidth ?? 50;
            container.style.gridTemplateColumns = `${left}fr 0.375rem ${100 - left}fr`;
        },

        expandPart(passageId) {
            this.expandedPartId = passageId;
            this.switchPart(passageId);
        },

        switchPart(passageId) {
            this.currentPassageId = passageId;
            const passage = this.passages.find((p) => p.id === passageId);
            if (passage?.questions?.length) {
                this.selectQuestion(passage.questions[0].number, false, { keepMobilePanel: true });
            }
            this.$nextTick(() => this.dragDrop?.schedulePassageInjection?.());
        },

        async markVisitedForCurrentQuestion() {
            if (this.isLocked || !this.endpoints?.visited) {
                return;
            }

            const question = this.questions[this.activeQuestionIndex];
            if (!question) {
                return;
            }

            try {
                await fetch(this.endpoints.visited, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({ question_id: question.id }),
                });
            } catch {
                // Best effort.
            }
        },

        selectQuestion(number, scroll = true, options = {}) {
            const keepMobilePanel = options.keepMobilePanel === true;
            const index = this.questions.findIndex((q) => q.number === number);
            if (index < 0) {
                return;
            }

            this.activateQuestion(number, { keepMobilePanel });

            this.$nextTick(() => this.highlightsController?.applyStoredHighlights(this.currentPassageId));
            this.$nextTick(() => this.sanitizePassageMarkers());

            if (scroll) {
                this.$nextTick(() => this.scrollToQuestion(number));
            }

            this.autosave?.savePosition();
            this.markVisitedForCurrentQuestion();
        },

        activateQuestion(number, options = {}) {
            const keepMobilePanel = options.keepMobilePanel === true;
            const index = this.questions.findIndex((q) => q.number === number);
            if (index < 0) {
                return;
            }

            this.activeQuestionIndex = index;
            this.currentQuestionNumber = number;

            const question = this.questions[index];
            if (question?.passage_id) {
                this.currentPassageId = question.passage_id;
                this.expandedPartId = question.passage_id;
            }

            if (!this.isDesktop() && !keepMobilePanel) {
                this.mobilePanel = 'questions';
            }

            this.highlightCurrentQuestion();
        },

        bindQuestionInteractions() {
            const pane = document.querySelector('.reading-test-questions-pane');
            if (!pane || pane.dataset.questionInteractionsBound === '1') {
                return;
            }

            pane.dataset.questionInteractionsBound = '1';

            const resolveQuestionNumber = (target) => {
                const input = target.closest('[data-question-number]');
                if (input?.dataset?.questionNumber) {
                    return Number(input.dataset.questionNumber);
                }

                const row = target.closest('.reading-test-question-row[data-question-number]');
                if (row?.dataset?.questionNumber) {
                    return Number(row.dataset.questionNumber);
                }

                return null;
            };

            const handleActivation = (event) => {
                if (this.isLocked) {
                    return;
                }

                const number = resolveQuestionNumber(event.target);
                if (number > 0) {
                    this.activateQuestion(number);
                }
            };

            pane.addEventListener('focusin', handleActivation);
            pane.addEventListener('pointerdown', handleActivation);
        },

        scrollToQuestion(number) {
            let row = document.querySelector(`[data-question-number="${number}"]`);

            if (!row) {
                const question = this.questions.find((item) => item.number === number);

                if (question?.group_id) {
                    row = document.querySelector(`#question-group-${question.group_id} .reading-test-question-row`)
                        ?? document.querySelector(`#question-group-${question.group_id}`);
                }
            }

            if (!row) {
                return;
            }

            const questionsPane = row.closest('.reading-test-questions-scroll')
                ?? row.closest('.reading-test-questions-pane');
            if (questionsPane) {
                const paneTop = questionsPane.getBoundingClientRect().top;
                const rowTop = row.getBoundingClientRect().top;
                const offset = questionsPane.scrollTop + (rowTop - paneTop) - (questionsPane.clientHeight / 2) + (row.offsetHeight / 2);
                questionsPane.scrollTo({ top: Math.max(0, offset), behavior: 'smooth' });
                return;
            }

            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        },

        showQuestionsPanel() {
            this.mobilePanel = 'questions';

            if (!this.currentQuestionNumber) {
                return;
            }

            this.$nextTick(() => this.scrollToQuestion(this.currentQuestionNumber));
        },

        highlightCurrentQuestion() {
            document.querySelectorAll('.reading-test-question-row').forEach((el) => {
                el.classList.remove('is-current');
            });

            if (!this.currentQuestionNumber) {
                return;
            }

            const current = document.querySelector(
                `.reading-test-question-row[data-question-number="${this.currentQuestionNumber}"]`,
            ) ?? (() => {
                const question = this.questions.find((item) => item.number === this.currentQuestionNumber);

                if (!question?.group_id) {
                    return null;
                }

                return document.querySelector(`#question-group-${question.group_id} .reading-test-question-row`);
            })();
            current?.classList.add('is-current');
        },

        goPrevious() {
            if (this.activeQuestionIndex <= 0) {
                return;
            }
            this.selectQuestion(this.questions[this.activeQuestionIndex - 1].number);
        },

        goNext() {
            if (this.activeQuestionIndex >= this.questions.length - 1) {
                return;
            }
            this.selectQuestion(this.questions[this.activeQuestionIndex + 1].number);
        },

        syncPassageForQuestion() {
            const question = this.questions[this.activeQuestionIndex];
            if (question?.passage_id) {
                this.currentPassageId = question.passage_id;
                this.expandedPartId = question.passage_id;
                this.currentQuestionNumber = question.number;
            }
        },

        questionNavClass(number) {
            const key = Number(number);
            const answeredMap = this.navigator?.answered_questions ?? {};
            const navQuestion = this.navigator?.questions?.[key] ?? this.navigator?.questions?.[String(key)];
            const flagged = Boolean(navQuestion?.flagged);

            let answered = null;

            if (Object.prototype.hasOwnProperty.call(answeredMap, key)) {
                answered = Boolean(answeredMap[key]);
            } else if (Object.prototype.hasOwnProperty.call(answeredMap, String(key))) {
                answered = Boolean(answeredMap[String(key)]);
            } else {
                answered = Boolean(navQuestion?.answered);
            }

            let status = 'unanswered';

            if (answered && flagged) {
                status = 'answered-flagged';
            } else if (flagged) {
                status = 'flagged';
            } else if (answered) {
                status = 'answered';
            } else if (navQuestion?.status) {
                status = navQuestion.status;
            }

            const classes = [`is-${status}`];

            if (number === this.currentQuestionNumber) {
                classes.push('is-current');
            }

            return classes.join(' ');
        },

        partAnsweredLabel(passage) {
            return this.autosave?.partAnsweredLabel(passage) ?? '';
        },

        toggleFlag(questionId, questionNumber) {
            if (this.isLocked) {
                return;
            }
            this.autosave?.toggleFlag(questionId, questionNumber);
        },

        isFlagged(questionId) {
            return Boolean(this.savedAnswers?.[questionId]?.flagged);
        },

        openReview() {
            this.reviewController?.openReview();
        },

        closeReview() {
            this.reviewController?.closeReview();
        },

        openSubmitModal() {
            this.reviewController?.openSubmitModal();
        },

        closeSubmitModal() {
            this.reviewController?.closeSubmitModal();
        },

        reviewUnanswered() {
            this.reviewController?.reviewUnansweredFromPanel();
        },

        reviewFlagged() {
            this.reviewController?.reviewFlaggedFromPanel();
        },

        continueTest() {
            this.reviewController?.closeReview();
        },

        submitAnyway() {
            this.reviewController?.submitAnyway();
        },

        selectFromReview(number) {
            this.reviewController?.selectFromReview(number);
        },

        togglePause() {
            this.reviewController?.pauseOverlay();
        },

        startResize(event) {
            this.resizing = true;
            const container = this.$refs.splitContainer;
            const divider = event.currentTarget;
            divider?.classList.add('is-dragging');

            const onMove = (moveEvent) => {
                if (!this.resizing || !container) {
                    return;
                }
                const rect = container.getBoundingClientRect();
                const next = ((moveEvent.clientX - rect.left) / rect.width) * 100;
                this.passageWidth = this.cbtUi?.clampPassageWidth
                    ? this.cbtUi.clampPassageWidth(next)
                    : Math.min(65, Math.max(35, next));
                this.syncSplitLayout();
            };
            const onUp = () => {
                this.resizing = false;
                divider?.classList.remove('is-dragging');
                this.cbtUi?.persistPassageWidth?.();
                window.removeEventListener('mousemove', onMove);
                window.removeEventListener('mouseup', onUp);
            };
            window.addEventListener('mousemove', onMove);
            window.addEventListener('mouseup', onUp);
        },

        openNotesPanel() {
            this.notesController?.newNote();
        },

        closeNotesPanel() {
            this.notesController?.closePanel();
        },

        filteredNotes() {
            return this.notesController?.filteredNotes() ?? [];
        },

        saveNoteDraft() {
            this.notesController?.scheduleSave();
        },

        editNote(note) {
            this.notesController?.editNote(note);
        },

        deleteNote(noteId) {
            this.notesController?.deleteNote(noteId);
        },

        closeTicketModal() {
            this.ticketsController?.closeModal();
        },

        openTicketModal(questionId, questionNumber) {
            this.ticketsController?.openModal(questionId, questionNumber);
        },

        submitTicket() {
            this.ticketsController?.submit();
        },
    };
}
