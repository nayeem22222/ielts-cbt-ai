export function readingPlayer(initialState) {
    const pad = (value) => String(value).padStart(2, '0');

    return {
        sections: initialState.sections ?? [],
        questions: initialState.questions ?? [],
        attempt: initialState.attempt ?? {},
        autosaveUrl: initialState.autosave_url,
        currentSectionId: initialState.attempt?.current_section_id ?? initialState.sections?.[0]?.id ?? null,
        activeQuestionId: initialState.attempt?.active_question_id ?? initialState.questions?.[0]?.id ?? null,
        highlightMode: false,
        notesOpen: false,
        mobilePanel: 'passage',
        autosaveStatus: 'saved',
        autosaveTimer: null,
        countdownSeconds: initialState.attempt?.time_remaining_seconds ?? 3600,
        countdownLabel: '60:00',
        answers: {},
        highlights: {},
        notes: {},
        flagged: {},
        questionTimings: {},
        questionVisits: {},
        timingInterval: null,

        init() {
            this.sections.forEach((section) => {
                this.highlights[section.id] = section.highlights ?? [];
                this.notes[section.id] = section.note ?? '';
            });

            this.questions.forEach((question) => {
                this.answers[question.id] = question.answer_text ?? '';
                this.flagged[question.id] = question.is_flagged ?? false;
                this.questionTimings[question.id] = 0;
                this.questionVisits[question.id] = 0;
            });

            this.syncCountdownLabel();
            this.selectQuestion(this.activeQuestionId ?? this.questions[0]?.id);
            this.$watch('answers', () => this.queueAutosave(), { deep: true });
            this.$watch('flagged', () => this.queueAutosave(), { deep: true });
            this.$watch('notes', () => this.queueAutosave(), { deep: true });

            window.setInterval(() => {
                if (this.countdownSeconds > 0) {
                    this.countdownSeconds -= 1;
                    this.syncCountdownLabel();
                }
            }, 1000);

            window.setInterval(() => this.saveNow(), 15000);

            this.timingInterval = window.setInterval(() => {
                if (this.activeQuestionId) {
                    this.questionTimings[this.activeQuestionId] = (this.questionTimings[this.activeQuestionId] ?? 0) + 1;
                }
            }, 1000);
        },

        get currentSection() {
            return this.sections.find((section) => section.id === this.currentSectionId) ?? this.sections[0] ?? null;
        },

        get activeQuestion() {
            return this.questions.find((question) => question.id === this.activeQuestionId) ?? null;
        },

        get navigatorQuestions() {
            return this.questions.map((question) => ({
                id: question.id,
                number: question.number,
                answered: Boolean((this.answers[question.id] ?? '').trim()),
                flagged: Boolean(this.flagged[question.id]),
                active: question.id === this.activeQuestionId,
            }));
        },

        syncCountdownLabel() {
            this.countdownLabel = `${pad(Math.floor(this.countdownSeconds / 60))}:${pad(this.countdownSeconds % 60)}`;
        },

        selectSection(sectionId) {
            this.currentSectionId = sectionId;
            this.mobilePanel = 'passage';
            this.queueAutosave();
        },

        selectQuestion(questionId) {
            this.activeQuestionId = questionId;
            const question = this.questions.find((item) => item.id === questionId);

            if (question) {
                const section = this.sections.find((item) =>
                    item.questions.some((entry) => entry.id === questionId)
                );
                if (section) {
                    this.currentSectionId = section.id;
                }
            }

            this.mobilePanel = 'questions';
            this.questionVisits[questionId] = (this.questionVisits[questionId] ?? 0) + 1;
            this.queueAutosave();
        },

        toggleFlag(questionId) {
            this.flagged[questionId] = !this.flagged[questionId];
        },

        toggleHighlightMode() {
            this.highlightMode = !this.highlightMode;
        },

        applyHighlight() {
            if (!this.highlightMode || !this.currentSection) {
                return;
            }

            const text = window.getSelection()?.toString().trim();

            if (!text) {
                return;
            }

            window.getSelection()?.removeAllRanges();

            if (!this.highlights[this.currentSection.id]) {
                this.highlights[this.currentSection.id] = [];
            }

            if (!this.highlights[this.currentSection.id].includes(text)) {
                this.highlights[this.currentSection.id].push(text);
            }

            this.queueAutosave();
        },

        restoreHighlights(sectionId) {
            return this.renderPassage(this.sections.find((item) => item.id === sectionId));
        },

        renderPassage(section) {
            if (!section) {
                return '';
            }

            let html = (section.stimulus_text ?? '').replace(/\n/g, '<br>');
            const snippets = this.highlights[section.id] ?? [];

            snippets.forEach((snippet) => {
                html = html.replaceAll(snippet, `<mark class="rounded bg-amber-200 px-0.5 dark:bg-amber-500/40">${snippet}</mark>`);
            });

            return html;
        },

        queueAutosave() {
            this.autosaveStatus = 'pending';
            window.clearTimeout(this.autosaveTimer);
            this.autosaveTimer = window.setTimeout(() => this.saveNow(), 1200);
        },

        async saveNow() {
            if (!this.autosaveUrl) {
                return;
            }

            this.autosaveStatus = 'saving';

            const payload = {
                current_section_id: this.currentSectionId,
                active_question_id: this.activeQuestionId,
                time_remaining_seconds: this.countdownSeconds,
                highlights: this.highlights,
                notes: this.notes,
                answers: this.questions.map((question) => ({
                    question_id: question.id,
                    answer_text: this.answers[question.id] ?? '',
                    is_flagged: Boolean(this.flagged[question.id]),
                })),
                question_timings: this.questions.map((question) => ({
                    question_id: question.id,
                    time_spent_seconds: this.questionTimings[question.id] ?? 0,
                    visit_count: this.questionVisits[question.id] ?? 0,
                })),
            };

            try {
                const response = await window.fetch(this.autosaveUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                if (!response.ok) {
                    throw new Error('Autosave failed');
                }

                this.autosaveStatus = 'saved';
            } catch (error) {
                this.autosaveStatus = 'error';
            }
        },
    };
}

window.readingPlayer = readingPlayer;
