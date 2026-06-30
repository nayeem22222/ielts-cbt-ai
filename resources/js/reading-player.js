export function readingPlayer(initialState) {
    const pad = (value) => String(value).padStart(2, '0');

    return {
        testTitle: initialState.test?.title ?? 'Reading Test',
        sections: initialState.sections ?? [],
        questions: initialState.questions ?? [],
        attempt: initialState.attempt ?? {},
        autosaveUrl: initialState.autosave_url,
        submitUrl: initialState.submit_url,
        submitting: false,
        isPaused: false,
        reviewOpen: false,
        submitModalOpen: false,
        reportModalOpen: false,
        currentSectionId: initialState.attempt?.current_section_id ?? initialState.sections?.[0]?.id ?? null,
        expandedPartId: initialState.attempt?.current_section_id ?? initialState.sections?.[0]?.id ?? null,
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
        countdownInterval: null,
        passageWidth: 50,
        resizing: false,
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
                    let selected = Array.isArray(question.selected_options)
                        ? [...question.selected_options]
                        : (question.answer_text ? question.answer_text.split(',').map((v) => v.trim()).filter(Boolean) : []);
                    const limit = Number(question.max_selections ?? 0);

                    if (Number.isFinite(limit) && limit > 0 && selected.length > limit) {
                        selected = selected.slice(0, limit);
                    }

                    this.multiAnswers[question.id] = selected;
                    this.answers[question.id] = selected.join(', ');
                }
            });

            this.syncCountdownLabel();
            this.selectQuestion(this.activeQuestionId ?? this.questions[0]?.id);
            this.startCountdown();

            this.$watch('answers', () => this.queueAutosave(), { deep: true });
            this.$watch('multiAnswers', () => this.queueAutosave(), { deep: true });
            this.$watch('flagged', () => this.queueAutosave(), { deep: true });
            this.$watch('notes', () => this.queueAutosave(), { deep: true });

            window.setInterval(() => this.saveNow(), 30000);

            this.timingInterval = window.setInterval(() => {
                if (this.isPaused || !this.activeQuestionId) {
                    return;
                }
                this.questionTimings[this.activeQuestionId] = (this.questionTimings[this.activeQuestionId] ?? 0) + 1;
            }, 1000);

            window.addEventListener('beforeunload', () => {
                if (!this.autosaveUrl) {
                    return;
                }

                window.fetch(this.autosaveUrl, {
                    method: 'PUT',
                    keepalive: true,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify(this.buildAutosavePayload()),
                });
            });

            document.addEventListener('click', () => {
                this.selectionToolbar.visible = false;
            });
        },

        startCountdown() {
            if (this.countdownInterval) {
                window.clearInterval(this.countdownInterval);
            }

            this.countdownInterval = window.setInterval(() => {
                if (this.isPaused || this.submitting) {
                    return;
                }

                if (this.countdownSeconds <= 0) {
                    window.clearInterval(this.countdownInterval);
                    this.submitTest(true);
                    return;
                }

                this.countdownSeconds -= 1;
                this.syncCountdownLabel();
            }, 1000);
        },

        get currentSection() {
            return this.sections.find((section) => section.id === this.currentSectionId) ?? this.sections[0] ?? null;
        },

        get activeQuestionIndex() {
            return this.questions.findIndex((item) => item.id === this.activeQuestionId);
        },

        get unansweredCount() {
            return this.questions.filter((question) => !this.isAnswered(question.id)).length;
        },

        minutesRemainingLabel() {
            const minutes = Math.max(0, Math.ceil(this.countdownSeconds / 60));
            return `${minutes} minute${minutes === 1 ? '' : 's'} remaining`;
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

        questionGroups(section) {
            if (!section?.questions?.length) {
                return [];
            }

            const groups = [];
            const sorted = [...section.questions].sort((a, b) => Number(a.number) - Number(b.number));

            sorted.forEach((question) => {
                const renderer = this.rendererForType(question.type);
                const key = `${question.type}:${renderer}`;
                let group = groups[groups.length - 1];

                if (!group || group.key !== key) {
                    group = {
                        id: `${section.id}-${groups.length}-${question.type}`,
                        key,
                        type: question.type,
                        renderer,
                        questions: [],
                    };
                    groups.push(group);
                }

                group.questions.push(question);
            });

            return groups.map((group) => ({
                ...group,
                title: this.groupTitle(group.questions),
                instruction: this.groupInstruction(group.type),
                options: this.groupOptions(group),
            }));
        },

        rendererForType(type) {
            const renderers = {
                matching_information: 'matching-information',
                matching_headings: 'matching-headings',
                summary_completion: 'summary-completion',
                sentence_completion: 'sentence-completion',
                note_completion: 'sentence-completion',
                table_completion: 'sentence-completion',
                flow_chart_completion: 'sentence-completion',
                diagram_label_completion: 'sentence-completion',
                true_false_ng: 'radio-table',
                yes_no_ng: 'radio-table',
                multiple_choice_single: 'multiple-choice',
                multiple_choice_multiple: 'multiple-answers',
                matching_features: 'matching-features',
                matching_sentence_endings: 'matching-sentence-endings',
                short_answer: 'short-answer',
            };

            return renderers[type] ?? 'short-answer';
        },

        groupTitle(questions) {
            const numbers = questions.map((question) => Number(question.number)).filter(Boolean);
            const first = Math.min(...numbers);
            const last = Math.max(...numbers);

            return first === last ? `Question ${first}` : `Questions ${first}-${last}`;
        },

        groupInstruction(type) {
            const instructions = {
                matching_information: 'Which section contains the following information? Choose the correct letter.',
                matching_headings: 'Choose the correct heading for each paragraph from the list of headings.',
                summary_completion: 'Complete the summary below. Choose from the list or write your answer in the blank.',
                sentence_completion: 'Complete the sentences below. Write your answer in the box.',
                note_completion: 'Complete the notes below. Write your answer in the box.',
                table_completion: 'Complete the table below. Write your answer in the box.',
                flow_chart_completion: 'Complete the flow chart below. Write your answer in the box.',
                diagram_label_completion: 'Label the diagram below. Write your answer in the box.',
                true_false_ng: 'Do the following statements agree with the information in the passage?',
                yes_no_ng: 'Do the following statements agree with the views or claims of the writer?',
                multiple_choice_single: 'Choose the correct answer.',
                multiple_choice_multiple: 'Choose the correct answers.',
                matching_features: 'Match each statement with the correct feature from the list.',
                matching_sentence_endings: 'Complete each sentence with the correct ending.',
                short_answer: 'Answer the questions below. Write your answer in the box.',
            };

            return instructions[type] ?? 'Answer the questions below.';
        },

        groupOptions(group) {
            const firstQuestion = group.questions[0];

            if (!firstQuestion) {
                return [];
            }

            if (group.renderer === 'radio-table') {
                return firstQuestion.options?.length ? firstQuestion.options : this.defaultBinaryOptions(firstQuestion);
            }

            if (firstQuestion.options?.length) {
                return firstQuestion.options;
            }

            if ([
                'matching-information',
                'matching-headings',
                'matching-features',
                'matching-sentence-endings',
            ].includes(group.renderer)) {
                return ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'].map((letter) => ({ label: letter, text: letter }));
            }

            return [];
        },

        groupOptionsForQuestion(group, question) {
            if (group.renderer === 'radio-table') {
                return question.options?.length ? question.options : this.defaultBinaryOptions(question);
            }

            return group.options;
        },

        matrixColumns(question) {
            if (question.options?.length) {
                return question.options.map((option) => option.label);
            }

            return ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        },

        summaryOptionLabel(question, label) {
            const option = question.options.find((item) => item.label === label);
            return option ? `${option.label}. ${option.text}` : label;
        },

        setAnswer(questionId, value) {
            this.answers[questionId] = value;
            this.queueAutosave();
        },

        clearAnswer(questionId) {
            this.answers[questionId] = '';
            this.queueAutosave();
        },

        isMultiSelected(questionId, value) {
            return (this.multiAnswers[questionId] ?? []).includes(value);
        },

        multiAnswerMaxSelections(questionId) {
            const question = this.questions.find((item) => item.id === questionId);

            if (!question) {
                return 2;
            }

            const section = this.sections.find((item) => item.id === question.section_id);
            const group = section
                ? this.questionGroups(section).find((entry) => entry.questions.some((item) => item.id === questionId))
                : null;

            const instructionSources = [
                group?.instruction ?? '',
                section?.instructions ?? '',
                question.prompt ?? '',
            ];

            for (const text of instructionSources) {
                const parsed = this.parseMaxSelectionsFromText(text);
                if (parsed) {
                    return parsed;
                }
            }

            const configured = Number(question.max_selections ?? 0);

            if (Number.isFinite(configured) && configured > 0) {
                return configured;
            }

            return 2;
        },

        parseMaxSelectionsFromText(text) {
            if (!text) {
                return null;
            }

            const words = {
                one: 1,
                two: 2,
                three: 3,
                four: 4,
                five: 5,
                six: 6,
            };

            const match = text.match(/\b(?:choose|select)\s+(one|two|three|four|five|six|\d+)\b/i);

            if (!match) {
                return null;
            }

            const token = match[1].toLowerCase();

            if (words[token]) {
                return words[token];
            }

            const numeric = Number.parseInt(token, 10);

            return Number.isFinite(numeric) && numeric > 0 ? numeric : null;
        },

        isMultiAnswerOptionDisabled(questionId, value) {
            const limit = this.multiAnswerMaxSelections(questionId);

            if (!Number.isFinite(limit) || limit <= 0) {
                return false;
            }

            const selected = this.multiAnswers[questionId] ?? [];

            if (selected.includes(value)) {
                return false;
            }

            return selected.length >= limit;
        },

        toggleMultiAnswer(questionId, value) {
            const current = [...(this.multiAnswers[questionId] ?? [])];
            const index = current.indexOf(value);
            const limit = this.multiAnswerMaxSelections(questionId);

            if (index >= 0) {
                current.splice(index, 1);
            } else {
                if (Number.isFinite(limit) && limit > 0 && current.length >= limit) {
                    return;
                }

                current.push(value);
            }

            this.multiAnswers[questionId] = current;
            this.answers[questionId] = current.join(', ');
            this.queueAutosave();
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

        reviewAnswerPreview(questionId) {
            const question = this.questions.find((item) => item.id === questionId);

            if (!question) {
                return '';
            }

            if (question.type === 'multiple_choice_multiple') {
                return (this.multiAnswers[questionId] ?? []).join(', ');
            }

            const answer = (this.answers[questionId] ?? '').trim();

            if (!answer) {
                return '—';
            }

            if (question.ui_pattern === 'summary_picker' && question.options?.length) {
                return this.summaryOptionLabel(question, answer);
            }

            return answer;
        },

        questionNavClass(questionId) {
            if (questionId === this.activeQuestionId) {
                if (this.isAnswered(questionId)) {
                    return 'bg-[#1b4332] text-white ring-2 ring-white shadow-sm';
                }

                return 'border-2 border-[#2D6A4F] bg-white text-[#2D6A4F] font-bold shadow-sm';
            }

            if (this.flagged[questionId]) {
                return 'bg-amber-100 text-amber-900 ring-1 ring-amber-300';
            }

            if (this.isAnswered(questionId)) {
                return 'bg-[#2D6A4F] text-white';
            }

            return 'bg-white text-neutral-600 ring-1 ring-neutral-300 hover:bg-neutral-50';
        },

        selectSection(sectionId) {
            this.currentSectionId = sectionId;
            this.expandedPartId = sectionId;
            this.mobilePanel = 'passage';
            this.queueAutosave();
        },

        expandPart(sectionId) {
            this.expandedPartId = sectionId;
            this.selectSection(sectionId);
            const section = this.sections.find((item) => item.id === sectionId);
            const firstQuestion = section?.questions?.[0];

            if (firstQuestion) {
                this.selectQuestion(firstQuestion.id);
            }
        },

        selectQuestion(questionId) {
            this.activeQuestionId = questionId;
            const question = this.questions.find((item) => item.id === questionId);

            if (question?.section_id) {
                this.currentSectionId = question.section_id;
                this.expandedPartId = question.section_id;
            } else {
                const section = this.sections.find((item) =>
                    item.questions.some((entry) => entry.id === questionId)
                );
                if (section) {
                    this.currentSectionId = section.id;
                    this.expandedPartId = section.id;
                }
            }

            this.mobilePanel = 'questions';
            this.questionVisits[questionId] = (this.questionVisits[questionId] ?? 0) + 1;
            this.queueAutosave();

            window.requestAnimationFrame(() => {
                document.getElementById(`question-${questionId}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        },

        goPrevious() {
            const index = this.activeQuestionIndex;

            if (index > 0) {
                this.selectQuestion(this.questions[index - 1].id);
            }
        },

        goNext() {
            const index = this.activeQuestionIndex;

            if (index >= 0 && index < this.questions.length - 1) {
                this.selectQuestion(this.questions[index + 1].id);
            }
        },

        toggleFlag(questionId) {
            this.flagged[questionId] = !this.flagged[questionId];
        },

        togglePause() {
            this.isPaused = !this.isPaused;
        },

        startResize(event) {
            const container = this.$refs.splitContainer;

            if (!container) {
                return;
            }

            this.resizing = true;
            const startX = event.clientX;
            const startWidth = this.passageWidth;

            const onMove = (moveEvent) => {
                const rect = container.getBoundingClientRect();
                const delta = moveEvent.clientX - startX;
                const next = startWidth + (delta / rect.width) * 100;
                this.passageWidth = Math.min(70, Math.max(30, next));
            };

            const onUp = () => {
                this.resizing = false;
                window.removeEventListener('mousemove', onMove);
                window.removeEventListener('mouseup', onUp);
            };

            window.addEventListener('mousemove', onMove);
            window.addEventListener('mouseup', onUp);
        },

        toggleHighlightMode() {
            this.highlightMode = !this.highlightMode;
        },

        openSubmitModal() {
            this.submitModalOpen = true;
        },

        handleTextSelection() {
            if (this.isPaused) {
                return;
            }

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

            const paragraphs = (section.stimulus_text ?? '').split(/\n\n+/).filter(Boolean);
            let html = '';

            paragraphs.forEach((paragraph, index) => {
                const label = String.fromCharCode(65 + index);
                const trimmed = paragraph.replace(/^[A-Z]\s+/, '');
                html += `<p class="mb-4"><strong class="mr-2 text-neutral-800">${label}</strong>${trimmed.replace(/\n/g, '<br>')}</p>`;
            });

            if (!paragraphs.length) {
                html = (section.stimulus_text ?? '').replace(/\n/g, '<br>');
            }

            html = html.replace(
                /\[\[(\d+)\]\]/g,
                '<span class="mr-1 inline-flex items-center rounded bg-amber-100 px-1.5 py-0.5 text-xs font-bold text-amber-800">Q$1</span>'
            );

            const snippets = this.highlights[section.id] ?? [];

            snippets.forEach((snippet) => {
                html = html.replaceAll(snippet, `<mark class="rounded bg-amber-200 px-0.5">${snippet}</mark>`);
            });

            return html;
        },

        queueAutosave() {
            this.autosaveStatus = 'pending';
            window.clearTimeout(this.autosaveTimer);
            this.autosaveTimer = window.setTimeout(() => this.saveNow(), 400);
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
            this.openSubmitModal();
        },

        async submitTest(skipConfirm = false) {
            if (!this.submitUrl || this.submitting) {
                return;
            }

            if (!skipConfirm && this.submitModalOpen === false) {
                this.openSubmitModal();
                return;
            }

            this.submitting = true;
            this.submitModalOpen = false;
            this.isPaused = true;

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
                this.isPaused = false;
                window.alert('Unable to submit your test. Please try again.');
            }
        },
    };
}

window.readingPlayer = readingPlayer;
