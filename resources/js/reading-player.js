export function readingPlayer(initialState) {
    const pad = (value) => String(value).padStart(2, '0');

    return {
        sections: initialState.sections ?? [],
        questions: initialState.questions ?? [],
        attempt: initialState.attempt ?? {},
        autosaveUrl: initialState.autosave_url,
        submitUrl: initialState.submit_url,
        submitting: false,
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
        multiAnswers: {},
        highlights: {},
        notes: {},
        flagged: {},
        questionTimings: {},
        questionVisits: {},
        timingInterval: null,
        selectionToolbar: { visible: false, top: 0, left: 0 },

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

                if (question.type === 'multiple_choice_multiple') {
                    this.multiAnswers[question.id] = Array.isArray(question.selected_options)
                        ? [...question.selected_options]
                        : (question.answer_text ? question.answer_text.split(',').map((v) => v.trim()).filter(Boolean) : []);
                }
            });

            this.syncCountdownLabel();
            this.selectQuestion(this.activeQuestionId ?? this.questions[0]?.id);
            this.$watch('answers', () => this.queueAutosave(), { deep: true });
            this.$watch('multiAnswers', () => this.queueAutosave(), { deep: true });
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

            document.addEventListener('click', () => {
                this.selectionToolbar.visible = false;
            });
        },

        get currentSection() {
            return this.sections.find((section) => section.id === this.currentSectionId) ?? this.sections[0] ?? null;
        },

        syncCountdownLabel() {
            this.countdownLabel = `${pad(Math.floor(this.countdownSeconds / 60))}:${pad(this.countdownSeconds % 60)}`;
        },

        formatPrompt(prompt) {
            return (prompt || '').replace(/\n/g, '<br>');
        },

        defaultBinaryOptions(question) {
            if (question.type === 'yes_no_ng') {
                return [
                    { label: 'Yes', text: 'Yes' },
                    { label: 'No', text: 'No' },
                    { label: 'NG', text: 'Not Given' },
                ];
            }

            return [
                { label: 'T', text: 'True' },
                { label: 'F', text: 'False' },
                { label: 'NG', text: 'Not Given' },
            ];
        },

        setAnswer(questionId, value) {
            this.answers[questionId] = value;
        },

        isMultiSelected(questionId, value) {
            return (this.multiAnswers[questionId] ?? []).includes(value);
        },

        toggleMultiAnswer(questionId, value) {
            const current = [...(this.multiAnswers[questionId] ?? [])];
            const index = current.indexOf(value);

            if (index >= 0) {
                current.splice(index, 1);
            } else {
                current.push(value);
            }

            this.multiAnswers[questionId] = current;
            this.answers[questionId] = current.join(', ');
        },

        isAnswered(questionId) {
            const question = this.questions.find((item) => item.id === questionId);

            if (question?.type === 'multiple_choice_multiple') {
                return (this.multiAnswers[questionId] ?? []).length > 0;
            }

            return Boolean((this.answers[questionId] ?? '').trim());
        },

        partAnsweredCount(sectionId) {
            const section = this.sections.find((item) => item.id === sectionId);

            if (!section) {
                return 0;
            }

            return section.questions.filter((question) => this.isAnswered(question.id)).length;
        },

        questionNavClass(questionId) {
            if (questionId === this.activeQuestionId) {
                return 'bg-brand-500 text-white ring-2 ring-brand-300';
            }

            if (this.flagged[questionId]) {
                return 'bg-amber-100 text-amber-800 ring-1 ring-amber-300 dark:bg-amber-500/20 dark:text-amber-200';
            }

            if (this.isAnswered(questionId)) {
                return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300';
            }

            return 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300';
        },

        selectSection(sectionId) {
            this.currentSectionId = sectionId;
            this.mobilePanel = 'passage';
            this.queueAutosave();
        },

        selectQuestion(questionId) {
            this.activeQuestionId = questionId;
            const question = this.questions.find((item) => item.id === questionId);

            if (question?.section_id) {
                this.currentSectionId = question.section_id;
            } else {
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

            window.requestAnimationFrame(() => {
                document.getElementById(`question-${questionId}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        },

        toggleFlag(questionId) {
            this.flagged[questionId] = !this.flagged[questionId];
        },

        toggleHighlightMode() {
            this.highlightMode = !this.highlightMode;
        },

        handleTextSelection() {
            const selection = window.getSelection();
            const text = selection?.toString().trim();

            if (!text || !this.currentSection) {
                this.selectionToolbar.visible = false;
                return;
            }

            const range = selection.getRangeAt(0);
            const rect = range.getBoundingClientRect();

            this.selectionToolbar = {
                visible: true,
                top: rect.top + window.scrollY - 44,
                left: rect.left + window.scrollX,
            };
        },

        applyHighlight() {
            if (!this.currentSection) {
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

            this.selectionToolbar.visible = false;
            this.highlightMode = false;
            this.queueAutosave();
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

        buildAutosavePayload() {
            return {
                current_section_id: this.currentSectionId,
                active_question_id: this.activeQuestionId,
                time_remaining_seconds: this.countdownSeconds,
                highlights: this.highlights,
                notes: this.notes,
                answers: this.questions.map((question) => {
                    const isMultiple = question.type === 'multiple_choice_multiple';
                    const selected = this.multiAnswers[question.id] ?? [];

                    return {
                        question_id: question.id,
                        answer_text: isMultiple ? selected.join(', ') : (this.answers[question.id] ?? ''),
                        selected_options: isMultiple ? selected : null,
                        is_flagged: Boolean(this.flagged[question.id]),
                    };
                }),
                question_timings: this.questions.map((question) => ({
                    question_id: question.id,
                    time_spent_seconds: this.questionTimings[question.id] ?? 0,
                    visit_count: this.questionVisits[question.id] ?? 0,
                })),
            };
        },

        async saveNow() {
            if (!this.autosaveUrl) {
                return;
            }

            this.autosaveStatus = 'saving';

            try {
                const response = await window.fetch(this.autosaveUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify(this.buildAutosavePayload()),
                });

                if (!response.ok) {
                    throw new Error('Autosave failed');
                }

                this.autosaveStatus = 'saved';
            } catch (error) {
                this.autosaveStatus = 'error';
            }
        },

        confirmSubmit() {
            if (this.submitting || !this.submitUrl) {
                return;
            }

            if (!window.confirm('Submit your reading test? You will not be able to change your answers.')) {
                return;
            }

            this.submitTest();
        },

        async submitTest() {
            if (!this.submitUrl || this.submitting) {
                return;
            }

            this.submitting = true;

            try {
                await this.saveNow();

                const response = await window.fetch(this.submitUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify(this.buildAutosavePayload()),
                });

                if (!response.ok) {
                    throw new Error('Submit failed');
                }

                const body = await response.json();
                const redirectUrl = body?.data?.redirect_url;

                if (redirectUrl) {
                    window.location.href = redirectUrl;
                    return;
                }

                throw new Error('Missing redirect URL');
            } catch (error) {
                this.submitting = false;
                window.alert('Unable to submit your test. Please try again.');
            }
        },
    };
}

window.readingPlayer = readingPlayer;
