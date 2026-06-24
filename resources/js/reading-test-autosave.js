const TEXT_DEBOUNCE_MS = 500;
const MAX_RETRIES = 3;

export function createReadingTestAutosave(component) {
    const debounceTimers = new Map();
    const unsavedQuestions = new Set();

    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    const flagUrlFor = (questionId) => {
        const template = component.endpoints?.toggleFlag ?? '';
        return template.replace('__QUESTION__', String(questionId));
    };

    const applyNavigator = (navigator) => {
        if (!navigator) {
            return;
        }

        component.navigator = navigator;
    };

    const questionRecord = (questionId) => component.savedAnswers?.[questionId] ?? null;

    const setQuestionRecord = (questionId, patch) => {
        component.savedAnswers = {
            ...(component.savedAnswers ?? {}),
            [questionId]: {
                ...(component.savedAnswers?.[questionId] ?? {}),
                ...patch,
            },
        };
    };

    const restoreAnswers = () => {
        const saved = component.savedAnswers ?? {};

        Object.values(saved).forEach((record) => {
            if (!record?.question_id) {
                return;
            }

            const inputs = document.querySelectorAll(
                `[data-question-id="${record.question_id}"]`,
            );

            inputs.forEach((input) => {
                if (input.type === 'radio') {
                    input.checked = input.value === record.answer;
                    return;
                }

                if (input.type === 'checkbox') {
                    input.checked = Array.isArray(record.answer_json)
                        ? record.answer_json.includes(input.value)
                        : false;
                    return;
                }

                if (input.tagName === 'SELECT' || input.type === 'text') {
                    input.value = record.answer ?? '';
                }
            });
        });

        applyNavigator(component.navigator);
    };

    const collectPayload = (input) => {
        if (!input.dataset.questionId || !input.dataset.questionType || !input.dataset.groupId) {
            return null;
        }

        const questionId = Number(input.dataset.questionId);
        const groupId = Number(input.dataset.groupId);
        const passageId = Number(input.dataset.passageId);
        const questionNumber = Number(input.dataset.questionNumber);
        const questionType = input.dataset.questionType;

        let answer = null;
        let answerJson = null;

        if (input.type === 'checkbox') {
            const groupSelector = `[data-question-id="${questionId}"][type="checkbox"]`;
            answerJson = Array.from(document.querySelectorAll(groupSelector))
                .filter((node) => node.checked)
                .map((node) => node.value);
        } else if (input.type === 'radio') {
            const checked = document.querySelector(
                `[data-question-id="${questionId}"][type="radio"]:checked`,
            );
            answer = checked?.value ?? null;
        } else {
            answer = input.value ?? null;
        }

        return {
            question_id: questionId,
            question_number: questionNumber,
            question_type: questionType,
            passage_id: passageId,
            group_id: groupId,
            answer,
            answer_json: answerJson,
        };
    };

    const showSaveWarning = (message = 'Answer not saved. Retrying…') => {
        component.saveWarning = message;
    };

    const clearSaveWarning = () => {
        component.saveWarning = null;
    };

    const markUnsaved = (questionNumber) => {
        unsavedQuestions.add(questionNumber);
        const nav = document.querySelector(
            `.reading-test-qnav[data-question-number="${questionNumber}"]`,
        );
        nav?.classList.add('is-unsaved');
    };

    const clearUnsaved = (questionNumber) => {
        unsavedQuestions.delete(questionNumber);
        const nav = document.querySelector(
            `.reading-test-qnav[data-question-number="${questionNumber}"]`,
        );
        nav?.classList.remove('is-unsaved');
    };

    const persistAnswer = async (payload, attempt = 0) => {
        if (!component.endpoints?.saveAnswer) {
            return null;
        }

        try {
            const response = await fetch(component.endpoints.saveAnswer, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                throw new Error(`Save failed (${response.status})`);
            }

            const body = await response.json();
            const data = body.data ?? {};

            setQuestionRecord(payload.question_id, {
                question_id: payload.question_id,
                question_number: payload.question_number,
                answer: data.answer?.answer ?? payload.answer,
                answer_json: data.answer?.answer_json ?? payload.answer_json,
                flagged: data.answer?.flagged ?? questionRecord(payload.question_id)?.flagged ?? false,
                answered: ['answered', 'answered-flagged'].includes(data.answered_status),
            });

            applyNavigator(data.navigator_status);
            clearUnsaved(payload.question_number);
            clearSaveWarning();

            return data;
        } catch (error) {
            if (attempt < MAX_RETRIES) {
                showSaveWarning();
                await new Promise((resolve) => setTimeout(resolve, 600 * (attempt + 1)));
                return persistAnswer(payload, attempt + 1);
            }

            showSaveWarning('Answer not saved. Check your connection.');
            markUnsaved(payload.question_number);
            return null;
        }
    };

    const queueSave = (input, immediate = false) => {
        if (component.isLocked) {
            return;
        }

        const payload = collectPayload(input);
        if (payload === null) {
            return;
        }

        const key = payload.question_id;

        if (debounceTimers.has(key)) {
            clearTimeout(debounceTimers.get(key));
            debounceTimers.delete(key);
        }

        const run = () => persistAnswer(payload);

        if (immediate) {
            run();
            return;
        }

        debounceTimers.set(
            key,
            setTimeout(() => {
                debounceTimers.delete(key);
                run();
            }, TEXT_DEBOUNCE_MS),
        );
    };

    const bindInputs = (root = document) => {
        const inputs = root.querySelectorAll(
            '.reading-test-input[data-question-id][data-question-type][data-group-id]',
        );

        inputs.forEach((input) => {
            if (input.dataset.autosaveBound === '1') {
                return;
            }

            input.dataset.autosaveBound = '1';

            if (input.type === 'radio' || input.type === 'checkbox' || input.tagName === 'SELECT') {
                input.addEventListener('change', () => queueSave(input, true));
                return;
            }

            input.addEventListener('input', () => queueSave(input, false));
        });
    };

    const savePosition = async () => {
        if (!component.endpoints?.savePosition || !component.currentPassageId) {
            return;
        }

        const question = component.questions[component.activeQuestionIndex];
        if (!question) {
            return;
        }

        try {
            await fetch(component.endpoints.savePosition, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    current_passage: component.currentPassageId,
                    current_question: question.id,
                }),
            });
        } catch {
            // Position saves are best-effort; answers are the priority.
        }
    };

    const toggleFlag = async (questionId, questionNumber) => {
        if (component.isLocked) {
            return;
        }

        const current = questionRecord(questionId);
        const nextFlagged = !(current?.flagged ?? false);

        try {
            const response = await fetch(flagUrlFor(questionId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ flagged: nextFlagged }),
            });

            if (!response.ok) {
                throw new Error('Flag save failed');
            }

            const body = await response.json();
            const data = body.data ?? {};

            setQuestionRecord(questionId, {
                question_id: questionId,
                question_number: questionNumber,
                flagged: nextFlagged,
                answer: data.answer?.answer ?? current?.answer ?? null,
                answer_json: data.answer?.answer_json ?? current?.answer_json ?? null,
                answered: ['answered', 'answered-flagged'].includes(data.answered_status),
            });

            applyNavigator(data.navigator_status);
        } catch {
            showSaveWarning('Could not save flag. Retrying…');
        }
    };

    const partAnsweredLabel = (passage) => {
        const part = component.navigator?.parts?.[passage.id];
        if (!part) {
            return '';
        }

        return `${part.answered} of ${part.total} answered`;
    };

    const handleOffline = () => {
        showSaveWarning('You are offline. Answers will retry when connection returns.');
    };

    const handleOnline = () => {
        if (unsavedQuestions.size === 0) {
            clearSaveWarning();
        }
    };

    window.addEventListener('offline', handleOffline);
    window.addEventListener('online', handleOnline);

    return {
        bindInputs,
        restoreAnswers,
        savePosition,
        toggleFlag,
        partAnsweredLabel,
    };
}
