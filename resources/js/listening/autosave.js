const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

export function createAutosave(state, ui, palette, offlineSync, review = null) {
    const autosaveConfig = state.config?.autosave ?? {};
    const debounceMs = autosaveConfig.debounce_ms ?? state.config.auto_save_debounce_ms ?? 700;
    const intervalMs = (autosaveConfig.bulk_interval_seconds ?? state.config.auto_save_interval_seconds ?? 10) * 1000;
    const retryAttempts = autosaveConfig.retry_attempts ?? 3;
    const retryDelayMs = autosaveConfig.retry_delay_ms ?? 1500;
    const useLocalBackup = autosaveConfig.use_local_storage_backup ?? true;

    let debounceTimers = new Map();
    let pending = new Map();
    let clientSequence = 0;
    let unsyncedCount = 0;

    const draftKey = () => state.recovery?.draft_key ?? `listening_attempt_${state.attempt_id}_draft`;

    const persistDraft = () => {
        if (!useLocalBackup) return;
        const answers = {};
        pending.forEach((answer, questionId) => {
            const question = state.questions?.find((q) => q.id === questionId);
            answers[question?.question_number ?? questionId] = {
                question_id: questionId,
                question_number: question?.question_number ?? questionId,
                answer,
                hash: hashAnswer(answer),
                updated_at: new Date().toISOString(),
            };
        });
        localStorage.setItem(
            draftKey(),
            JSON.stringify({
                attempt_id: state.attempt_id,
                updated_at: new Date().toISOString(),
                current_section_number: state.currentSection,
                current_question_number: state.activeQuestionNumber ?? state.currentQuestion,
                answers,
                pending_sync: [...pending.keys()],
            }),
        );
    };

    const hashAnswer = (answer) => {
        try {
            return btoa(unescape(encodeURIComponent(JSON.stringify(answer ?? null)))).slice(0, 64);
        } catch {
            return String(Date.now());
        }
    };

    const applyResponse = (data) => {
        if (data.palette) {
            palette.update(data.palette);
            review?.updateFromPalette(data.palette);
        }
        if (data.navigation) {
            state.currentSection = data.navigation.current_section_number ?? state.currentSection;
            const questionNumber = data.navigation.current_question_number
                ?? state.activeQuestionNumber
                ?? state.currentQuestion;
            state.activeQuestionNumber = questionNumber;
            state.currentQuestion = questionNumber;
        }
        state.total_answered = data.total_answered ?? state.total_answered;
    };

    const requestWithRetry = async (url, body, attempt = 0) => {
        if (!navigator.onLine) {
            offlineSync?.markOffline();
            throw new Error('offline');
        }

        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf(),
            },
            body: JSON.stringify(body),
        });

        if (!res.ok) {
            if (attempt < retryAttempts) {
                await new Promise((resolve) => setTimeout(resolve, retryDelayMs));
                return requestWithRetry(url, body, attempt + 1);
            }
            throw new Error('save failed');
        }

        offlineSync?.markOnline();
        return res.json();
    };

    const flushSingle = async (questionId, answer) => {
        clientSequence += 1;
        ui.setSaveStatus('Saving…');

        try {
            const data = await requestWithRetry(state.routes.autosave ?? state.routes.save_answer, {
                question_id: questionId,
                answer,
                client_answer_hash: hashAnswer(answer),
                client_sequence: clientSequence,
                client_saved_at: new Date().toISOString(),
            });

            pending.delete(questionId);
            if (data.skipped) {
                ui.setSaveStatus('Saved');
            } else {
                ui.setSaveStatus('Saved');
            }
            applyResponse(data);
            unsyncedCount = Math.max(0, unsyncedCount - 1);
            ui.setUnsyncedCount?.(unsyncedCount);
            persistDraft();
        } catch {
            unsyncedCount += 1;
            ui.setSaveStatus('Retry failed');
            ui.setUnsyncedCount?.(unsyncedCount);
            offlineSync?.markOffline();
            persistDraft();
        }
    };

    const queueSave = (questionId, answer) => {
        pending.set(questionId, answer);
        persistDraft();
        clearTimeout(debounceTimers.get(questionId));
        debounceTimers.set(
            questionId,
            setTimeout(() => flushSingle(questionId, answer), debounceMs),
        );
    };

    const bulkFlush = async () => {
        if (pending.size === 0) return null;

        const answers = [...pending.entries()].map(([question_id, answer]) => ({
            question_id,
            answer,
            client_answer_hash: hashAnswer(answer),
            client_sequence: ++clientSequence,
            client_saved_at: new Date().toISOString(),
        }));

        ui.setSaveStatus('Saving…');

        try {
            const data = await requestWithRetry(state.routes.autosave_bulk ?? state.routes.bulk_save, {
                answers,
                current_section_number: state.currentSection,
                current_question_number: state.activeQuestionNumber ?? state.currentQuestion,
            });

            pending.clear();
            ui.setSaveStatus('Saved');
            applyResponse(data);
            unsyncedCount = 0;
            ui.setUnsyncedCount?.(0);
            persistDraft();
            return data;
        } catch {
            ui.setSaveStatus('Offline');
            unsyncedCount = pending.size;
            ui.setUnsyncedCount?.(unsyncedCount);
            offlineSync?.markOffline();
            persistDraft();
            return null;
        }
    };

    const flushBeforeNavigation = async () => {
        clearTimeout([...debounceTimers.values()]);
        debounceTimers.clear();
        await bulkFlush();
    };

    const hasUnsynced = () => pending.size > 0 || unsyncedCount > 0;

    const clearDraft = () => {
        localStorage.removeItem(draftKey());
        pending.clear();
        unsyncedCount = 0;
    };

    setInterval(bulkFlush, intervalMs);

    return { queueSave, bulkFlush, flushBeforeNavigation, hasUnsynced, clearDraft, hashAnswer };
}
